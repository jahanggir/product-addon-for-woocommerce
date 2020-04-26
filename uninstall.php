<?php
/**
 * If uninstall is not called from WordPress, exit.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;

if ( is_multisite() ) {

	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
		delete_option( 'product_helium_addon_enabled' );
		delete_option( 'product_helium_addon_cost' );
		delete_option( 'product_helium_addon_message' );

	if ( $blogs ) {

	 	foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			delete_option( 'product_helium_addon_enabled' );
			delete_option( 'product_helium_addon_cost' );
			delete_option( 'product_helium_addon_message' );

			// info: optimize table.
			$GLOBALS['wpdb']->query( 'OPTIMIZE TABLE `' . $GLOBALS['wpdb']->prefix . 'options`' );
			restore_current_blog();
		}
	}
} else {
	delete_option( 'product_helium_addon_enabled' );
	delete_option( 'product_helium_addon_cost' );
	delete_option( 'product_helium_addon_message' );

	// info: optimize table.
	$GLOBALS['wpdb']->query( 'OPTIMIZE TABLE `' . $GLOBALS['wpdb']->prefix . 'options`' );
}
