<?php
/*
Plugin Name: FlippingBook
Plugin URI: https://flippingbook.com/
Description: Embed FlippingBook interactive publications into your blog post or WordPress webpage quickly and easily.
Version: 1.3.0
Author: FlippingBook Team
Author URI: http://flippingbook.com/about
*/
?>
<?php
/*  Copyright 2016  FlippingBook Team (email: chernov.sergey@flippingbook.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
include ('mustache.php');

class Flippingbook {
	public $shortcode_tag = 'flippingbook';
	public $cld_mask = '#https?://(.*\.)?(cld\.mobi)|(cld\.bz)/.*#i';
	public $cld_domain_mask = '#(cld\.mobi)|(cld\.bz)/.*#';
	public $flippingbook_mask = '#https?://(www\.)?(online)(.{0,7})?\.flippingbook\.com/view/.*#i';
	public $flippingbook_domain_mask ='#(online)(.{0,7})?.flippingbook\.com#';

	function Flippingbook() {
		$cld_provider = plugins_url().'/flippingbook/oembed.php?format={format}';
		$flippingbook_provider = 'https://flippingbook.com/____fbonline/oembed/';
		wp_oembed_add_provider( $this->cld_mask, $cld_provider, true );
		wp_oembed_add_provider( $this->flippingbook_mask, $flippingbook_provider, true );
		add_shortcode( $this->shortcode_tag, array( $this, 'shortcode_handler' ) );
	}

	function get_embed_url_from_api( $domain, $url ) {
		if ( preg_match($this->flippingbook_domain_mask, $domain) !== false ) {
			$embed_url_api_response = wp_remote_get('https://' . $domain . '/EmbedScriptUrl.aspx?url=' . $url);
			if (!is_wp_error($embed_url_api_response)) {
				$embed_url_api_response_body = wp_remote_retrieve_body($embed_url_api_response);
				$embed_script_url = filter_var(trim($embed_url_api_response_body), FILTER_VALIDATE_URL);
			} else {
				if (WP_DEBUG) {
					$error_string = $embed_url_api_response->get_error_message();
					echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
				}
				return false;
			}
		} else if (preg_match($this->cld_mask, $domain) !== false) {
			$embed_script_url = 'https://'.$domain.'/e/embed.js?url='.$url;
		} else {
			return false;
		}

		//если URL эмбеда недоступен возвращаем false
		$embed_url_response = wp_remote_get($embed_script_url);

		if (is_wp_error($embed_url_response)) {
			if (WP_DEBUG) {
				$error_string = $embed_url_response->get_error_message();
				echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
			}
			return false;
		}
		if ($embed_url_response['response']['code'] >= 400) {
			if (WP_DEBUG) {
				echo '<div id="message" class="error"><p>HTTP Error '.$embed_url_response['response']['code'].' while getting embed script from url: '.$embed_script_url.'</p></div>';
			}
			return false;
		}

		return $embed_script_url;
	}

	function get_embed_url( $domain, $url ) {
		//checking if a recent url info is already stored
		$embed_options = get_option( 'flippingbook_embed' );
		if ( !empty( $embed_options ) && isset( $embed_options[$url.'_url'] ) && isset( $embed_options[$url.'_last_update'] ) && ( ( time() - $embed_options[$url.'_last_update'] ) < 86400 ) ) {
			return $embed_options[$url.'_url'];
		} else {
			$embed_script_url = $this->get_embed_url_from_api( $domain, $url );
			//storing new embed script url in a Wordpress option
			if ($embed_script_url !== false) {
				$embed_options[$url.'_url'] = $embed_script_url;
				$embed_options[$url.'_last_update'] = time();
				update_option( 'flippingbook_embed', $embed_options );
			}
			return $embed_script_url;
		}
	}

	function get_embed_template() {
		$cached_template = get_option( 'flippingbook_template' );
		if ( !empty( $cached_template ) && isset( $cached_template['last_update'] ) && ( ( time() - $cached_template['last_update'] ) < 86400 ) ) {
			return $cached_template['template_text'];
		} else {
			$request_data = json_encode(
				array(
					'Services' => array(
						array(
							'$type' => 'Mediaparts.Infrastructure.ServiceRegistry.ExactServiceRequest',
							'Subsystem' => 'Publisher2',
							'Service' => 'embed-code-template'
						)
					)
				)
			);

			$embed_template_api_response = wp_remote_post( 'https://registry.flippingbook.com/RegistryThin.svc/json/GetServices', array('body' => $request_data));

			if ( !is_wp_error( $embed_template_api_response ) ) {
				$embed_template_api_response_body = wp_remote_retrieve_body( $embed_template_api_response );
				$embed_template_api_response_body = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $embed_template_api_response_body);
				$response_array = json_decode($embed_template_api_response_body, true);

				if (json_last_error() !== 0) {
					if ( WP_DEBUG ) {
						echo '<div id="message" class="error"><p>Error reading server response</p></div>';
					}
					return false;
				}

				if ($response_array["Responses"][0]["Success"] !== true || $response_array["Responses"][0]["Error"] !== null) {
					if ( WP_DEBUG ) {
						echo '<div id="message" class="error"><p>Server returned error</p></div>';
					}
					return false;
				}

				$endpoint = $response_array["Responses"][0]["Services"][0]["Endpoints"][0];
				$template_url = "https://".$endpoint["Host"]."/".$endpoint["Path"];
				$template_text_response = wp_remote_get($template_url);

				if ( !is_wp_error( $template_text_response ) ) {
					$template_text_response_body = wp_remote_retrieve_body( $template_text_response );
					$template_text = trim( $template_text_response_body );

					$template_option['template_text'] = $template_text;
					$template_option['last_update'] = time();
					update_option( 'flippingbook_template', $template_option );

					return $template_text;
				} else {
					if ( WP_DEBUG ) {
						$error_string = $template_text_response->get_error_message();
						echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
					}
					return false;
				}

			} else {
				if ( WP_DEBUG ) {
					$error_string = $embed_url_api_response->get_error_message();
					echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
				}
				return false;
			}
		}
	}

	function get_embed_code( $content, $a, $prefix, $embed_script_url ) {
		$m = new Mustache;
		$template = $this->get_embed_template();

		$data = $a;
		$data['prefix'] = $prefix;
		$data['script'] = $embed_script_url;
		$data['url'] = $content;
		$data['method'] = "wp";
		$data['version'] = "WP-1.3.0";

		if ( strpos($data['width'], 'px') === false && strpos($data['width'], '%') === false ) {
			$data['width'] = $data['width']."px";
		}

		if ( strpos($data['height'], 'px') === false && strpos($data['height'], '%') === false ) {
			$data['height'] = $data['height']."px";
		}

		return $m->render($template, $data);
	}

	function shortcode_handler( $atts , $content ) {
		$url = parse_url( $content );
		if(!empty($url)){
			$domain = $url['host'];
			$a = shortcode_atts(
				array(
					'width' => '740px',
					'height' => '480px',
					'lightbox' => 'false',
					'title' => 'Generated by FlippingBook Publisher'
				), $atts );

			if ( strpos($domain,'flippingbook.com')  !== false )
			{
				if( preg_match( $this->flippingbook_domain_mask, $domain ) !== false )
				{
					$embed_script_url = $this->get_embed_url( $domain, $content );
					if ( !empty($embed_script_url) && !stripos($embed_script_url, 'embed/boot.js') ) {
						return $this->get_embed_code( $content, $a, 'fbo', $embed_script_url );
					} else {
						$embed_script_url = 'https://'.$domain.'/content/embed/boot.js';
					}
				} else {
					$embed_script_url = 'https://'.$domain.'/____fbonline/content/embed/boot.js';
				}
				$html = $this->get_embed_code( $content, $a, 'fb', $embed_script_url );

			} else {
				$domain_names = explode( ".", $domain );
				if( count( $domain_names ) < 2 ){
					return "FlippingBook shortcode is not correct";
				}
				$bottom_domain = $domain_names[count( $domain_names ) - 2] . "." . $domain_names[count( $domain_names ) - 1];
				$embed_script_url = $this->get_embed_url( $bottom_domain, $content );

				if ( !empty( $embed_script_url ) && !stripos($embed_script_url, 'embed-boot/boot.js') ) {
					return $this->get_embed_code( $content, $a, 'fbc', $embed_script_url );
				} else {
					$embed_script_url = 'https://'.$bottom_domain.'/content/embed-boot/boot.js';
				}
				$html = $this->get_embed_code( $content, $a, 'cld', $embed_script_url );
			}
			return $html;
		}
		return "FlippingBook shortcode is not correct";
	}
}
$flippingbook = new Flippingbook();

?>