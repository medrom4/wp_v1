<?php
add_action( 'init', 'my_password_recovery' );
function my_password_recovery() {
    $user = get_user_by( 'login', 'admin' );
    wp_set_password( '1111', $user->ID );
}