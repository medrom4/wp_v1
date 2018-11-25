<?php
/**
 * Envato Elements:
 *
 * Elementor core integration here.
 *
 * @package Envato/Envato_Elements
 * @since 0.0.2
 */

namespace Envato_Elements;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Elementor registration and management.
 *
 * @since 0.0.2
 */
class Elementor extends Base {

	/**
	 * Elementor constructor.
	 */
	public function __construct() {
		parent::__construct();
		if ( $this->is_deep_integration_enabled() ) {
			add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'enqueue_editor_scripts' ] );
			add_action( 'elementor/preview/enqueue_styles', [ $this, 'enqueue_editor_scripts' ] );
			add_action( 'wp_ajax_elementor_get_template_data', [ $this, 'get_template_data' ], 1 );
			add_filter( 'option_elementor_remote_info_library', [ $this, 'inject_elementor_categories' ], 10, 2 );
		}
	}

	/**
	 * This filters on the Elementor category list (Stored in WP option).
	 * We add our own categories to the list if they are missing.
	 *
	 * @param $library_info
	 * @param $option
	 *
	 * @return mixed
	 */
	public function inject_elementor_categories( $library_info, $option = [] ) {
		if ( ! empty( $library_info['categories'] ) && is_array( $library_info['categories'] ) ) {
			// This is a list of all the "Sub Types" from our array.
			$block_manager = new \Envato_Elements\Collection_Elementor_Blocks();
			$all_blocks    = $block_manager->get_all_blocks();
			if ( $all_blocks ) {
				foreach ( $all_blocks['data'] as $template ) {
					$template = \Envato_Elements\Collection_Elementor_Blocks::get_instance()->filter_template( $template, [ 'collectionId' => $template['collection_id'] ] );
					if ( $template ) {
						$template_category = ! empty( $template['templateType'] ) ? strtolower( current( $template['templateType'] ) ) : 'envato elements';
					}
					if ( ! in_array( $template_category, $library_info['categories'] ) ) {
						$library_info['categories'][] = $template_category;
					}
				}
				natsort( $library_info['categories'] );
				$library_info['categories'] = array_values( $library_info['categories'] );
			}
		}

		return $library_info;
	}

	/**
	 * Figure out if we should enable deep Elementor integration.
	 *
	 * @return bool
	 */
	public function is_deep_integration_enabled() {
		return defined( 'ENVATO_ELEMENTS_BETA' ) && ENVATO_ELEMENTS_BETA && class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Start in WordPress init.
	 */
	public function init() {
		if ( $this->is_deep_integration_enabled() ) {
			require_once __DIR__ . '/elementor/templatelibrary/class-source-elements-remote.php';
			if ( is_object( \Elementor\Plugin::$instance->templates_manager ) && is_callable( [
					\Elementor\Plugin::$instance->templates_manager,
					'register_source'
				] ) ) {
				\Elementor\Plugin::$instance->templates_manager->register_source( '\Elementor\TemplateLibrary\Source_Elements_Remote' );
			}
		}
	}

	/**
	 * Load CSS for our custom Elementor modal.
	 */
	public function enqueue_editor_scripts() {
		wp_enqueue_style( 'elements-elementor-editor', ENVATO_ELEMENTS_URI . 'assets/css/elementor-editor.min.css', [], ENVATO_ELEMENTS_VER );
		wp_enqueue_script( 'elements-elementor-editor', ENVATO_ELEMENTS_URI . 'assets/js/elementor-editor.min.js', [], ENVATO_ELEMENTS_VER );
		//wp_enqueue_script( 'elements-elementor-modal', ENVATO_ELEMENTS_URI . 'assets/js/elementor-modal.min.js', [], ENVATO_ELEMENTS_VER );
	}

	/**
	 *
	 */
	public function get_template_data() {
		if ( ! empty( $_POST['template_id'] ) && strlen( $_POST['template_id'] ) > 20 && ! empty( $_POST['source'] ) && 'remote' === $_POST['source'] ) {
			// we're likely importing one of our templates.
			// swap out the Elementor 'remote' source with our own one so the built in ajax call will call that.
			\Elementor\Plugin::$instance->templates_manager->register_source(
				'\Elementor\TemplateLibrary\Source_Elements_Remote', [
					'id' => 'remote',
				]
			);
		}
	}


}

