<?php

/**
 * Plugin Name: Add Helium Addon
 * Plugin URI: https://jaman.xyz
 * Description: Add a checkbox to your products to charge extra fee for heliums
 * Version: 2.0.0
 * Author: Jahanggir Jaman
 * Author URI: https://jaman.xyz
 * Requires at least: 5
 * Tested up to: 5.4
 * Text Domain: product-helium-addon-for-woocommerce
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Original Author: mikejolley, tabrisrp
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WC_Product_Helium_Addon')) :
	define('WC_Product_Helium_Addon_PATH', realpath(plugin_dir_path(__FILE__)));

	require(WC_Product_Helium_Addon_PATH . '/classes/class-wc-product-helium-addon.php');

	register_activation_hook(__FILE__, array('WC_Product_Helium_Addon', 'install'));
	add_action('plugins_loaded', array('WC_Product_Helium_Addon', 'init'));

endif;
