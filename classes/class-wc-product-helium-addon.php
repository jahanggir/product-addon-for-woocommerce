<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main class for product helium addon.
 *
 * @since 1.0.0
 */
class WC_Product_Helium_Addon
{

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string VERSION plugin version.
	 */
	const VERSION = '2';

	/**
	 * Plugin settings fields.
	 *
	 * @since 1.0.0
	 * @var array $settings settings fields.
	 */
	public $settings;

	/**
	 * Plugin option to enable the feature.
	 *
	 * @since 1.0.0
	 * @var string $helium_addon_enabled plugin option.
	 */
	public $helium_addon_enabled;

	/**
	 * Plugin option to set price.
	 *
	 * @since 1.0.0
	 * @var int $helium_addon_cost plugin option to set price.
	 */
	public $helium_addon_cost;

	/**
	 * Plugin option to set weight.
	 *
	 * @since 2.0.0
	 * @var int $helium_addon_weight plugin option to set price.
	 */
	public $helium_addon_weight;

	/**
	 * Plugin option to display text next to checkbox for activating the helium addon.
	 *
	 * @since 1.0.0
	 * @var string $product_helium_addon_message Message to be displayed.
	 */
	public $product_helium_addon_message;

	/**
	 * Construct function.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		$this->helium_addon_enabled         = get_option('product_helium_addon_enabled');
		$this->helium_addon_cost            = get_option('product_helium_addon_cost', 0);
		$this->helium_addon_weight          = get_option('product_helium_addon_weight', 0);
		$this->product_helium_addon_message = get_option('product_helium_addon_message');
	}

	/**
	 * Initialise instance.
	 *
	 * @since 1.3.1
	 */
	public static function init()
	{
		$self = new self();

		// Load plugin text domain.
		add_action('init', array($self, 'load_plugin_textdomain'));

		// Display before add to cart button on the front end.
		add_action('woocommerce_before_add_to_cart_button', array($self, 'helium_option_html'), 10);

		// Filters for cart actions.
		add_filter('woocommerce_add_cart_item_data', array($self, 'add_cart_item_data'), 10, 2);
		add_filter('woocommerce_get_cart_item_from_session', array($self, 'get_cart_item_from_session'), 10, 2);
		add_filter('woocommerce_get_item_data', array($self, 'get_item_data'), 10, 2);
		add_filter('woocommerce_add_cart_item', array($self, 'add_cart_item'), 10, 1);
		add_action('woocommerce_add_order_item_meta', array($self, 'add_order_item_meta'), 10, 2);

		// Write Panels.
		add_action('woocommerce_product_options_pricing', array($self, 'write_panel'));
		add_action('woocommerce_process_product_meta', array($self, 'write_panel_save'));

		// Admin.
		add_action('woocommerce_settings_general_options_end', array($self, 'display_admin_settings'));
		add_action('woocommerce_update_options_general', array($self, 'save_admin_settings'));
	}

	/**
	 * Runs on plugin activation to initialize options.
	 *
	 * @since 1.0.0
	 */
	public static function install()
	{
		add_option('product_helium_addon_enabled', false);
		add_option('product_helium_addon_cost', '0');
		// Add option to set helium weight globally
		add_option('product_helium_addon_weight', '0');
		// Translators: %s is the price for the helium addon.
		add_option('product_helium_addon_message', sprintf(__('Add helium for %s?', 'product-helium-addon-for-woocommerce'), '{price}'));
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain()
	{
		$locale = apply_filters('plugin_locale', get_locale(), 'product-helium-addon-for-woocommerce');

		load_textdomain('product-helium-addon-for-woocommerce', trailingslashit(WP_LANG_DIR) . 'product-helium-addon-for-woocommerce/product-helium-addon-for-woocommerce-' . $locale . '.mo');
		load_plugin_textdomain('product-helium-addon-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	/**
	 * Basic integration with WooCommerce Currency Switcher, developed by Aelia
	 * (http://aelia.co). This method can be used by any 3rd party plugin to
	 * return prices converted to the active currency.
	 *
	 * @param double $price The source price.
	 * @param string $to_currency The target currency. If empty, the active currency
	 * will be taken.
	 * @param string $from_currency The source currency. If empty, WooCommerce base
	 * currency will be taken.
	 * @return double The price converted from source to destination currency.
	 * @author Aelia <support@aelia.co>
	 * @link http://aelia.co
	 */
	protected function get_price_in_currency($price, $to_currency = null, $from_currency = null)
	{
		// If source currency is not specified, take the shop's base currency as a default.
		if (empty($from_currency)) {
			$from_currency = get_option('woocommerce_currency');
		}

		/**
		 * If target currency is not specified, take the active currency as a default.
		 * The Currency Switcher sets this currency automatically, based on the context. Other
		 * plugins can also override it, based on their own custom criteria, by implementing
		 * a filter for the "woocommerce_currency" hook.
		 *
		 * For example, a subscription plugin may decide that the active currency is the one
		 * taken from a previous subscription, because it's processing a renewal, and such
		 * renewal should keep the original prices, in the original currency.
		 */
		if (empty($to_currency)) {
			$to_currency = get_woocommerce_currency();
		}

		/**
		 * Call the currency conversion filter. Using a filter allows for loose coupling. If the
		 * Aelia Currency Switcher is not installed, the filter call will return the original
		 * amount, without any conversion being performed. Your plugin won't even need to know if
		 * the multi-currency plugin is installed or active.
		 */
		return apply_filters('wc_aelia_cs_convert', $price, $from_currency, $to_currency);
	}

	/**
	 * Show the Add Helium Checkbox on the frontend
	 *
	 * @access public
	 * @return void
	 */
	public function helium_option_html()
	{
		global $post;

		$is_addable = get_post_meta($post->ID, '_is_helium_addable', true);

		if ('' === $is_addable && $this->helium_addon_enabled) {
			$is_addable = 'yes';
		}

		if ('yes' === $is_addable) {

			$current_value = (isset($_REQUEST['helium_add']) && !empty(absint($_REQUEST['helium_add']))) ? 1 : 0;

			$cost = get_post_meta($post->ID, '_helium_addon_cost', true);

			// helium weight from product
			$weight = get_post_meta($post->ID, '_helium_addon_weight', true);

			if ('' === $cost) {
				$cost = $this->helium_addon_cost;
			}

			if ('' === $weight) {
				// global helium addon weight
				$weight = $this->helium_addon_weight;
			}

			$price_text = $cost > 0 ? wc_price($this->get_price_in_currency($cost)) : __('free', 'product-helium-addon-for-woocommerce');

			wc_get_template('helium-add.php', array(
				'product_helium_addon_message' => $this->product_helium_addon_message,
				'current_value'             => $current_value,
				'price_text'                => $price_text,
			), 'product-helium-addon-for-woocommerce', WC_Product_Helium_Addon_PATH . '/templates/');
		}
	}

	/**
	 * When added to cart, save any helium data
	 *
	 * @access public
	 * @param mixed $cart_item_meta The cart item data.
	 * @param mixed $product_id Product ID or object.
	 * @return array an Array of item meta
	 */
	public function add_cart_item_data($cart_item_meta, $product_id)
	{
		$is_addable = get_post_meta($product_id, '_is_helium_addable', true);

		if ('' === $is_addable && $this->helium_addon_enabled) {
			$is_addable = 'yes';
		}

		if (!empty($_POST['helium_add']) && 'yes' === $is_addable) {
			$cart_item_meta['helium_add'] = true;
		}

		return $cart_item_meta;
	}

	/**
	 * Get the helium data from the session on page load
	 *
	 * @access public
	 * @param mixed $cart_item Array of cart item data.
	 * @param mixed $values an array of values.
	 * @return array an array of cart item data
	 */
	public function get_cart_item_from_session($cart_item, $values)
	{
		if (empty($values['helium_add'])) {
			return $cart_item;
		}

		$cart_item['helium_add'] = true;

		$cost = get_post_meta($cart_item['product_id'], '_helium_addon_cost', true);

		// Get weight from session
		$weight = get_post_meta($cart_item['product_id'], '_helium_addon_weight', true);

		if ('' === $cost) {
			$cost = $this->helium_addon_cost;
		}

		// if unset on product level get from global 
		if ('' === $weight) {
			$weight = $this->helium_addon_weight;
		}

		$product = wc_get_product($values['variation_id'] ? $values['variation_id'] : $values['product_id']);

		$cart_item['data']->set_price($product->get_price() + $this->get_price_in_currency($cost));

		// set weight
		$cart_item['data']->set_weight($product->get_price() + $weight);

		return $cart_item;
	}

	/**
	 * Display Helium data if present in the cart
	 *
	 * @access public
	 * @param mixed $item_data array of helium addon data.
	 * @param mixed $cart_item cart item.
	 * @return array an array for the helium addon data
	 */
	public function get_item_data($item_data, $cart_item)
	{
		if (empty($cart_item['helium_add'])) {
			return $item_data;
		}

		$item_data[] = array(
			'name'    => __('Helium Added', 'product-helium-addon-for-woocommerce'),
			'value'   => __('Yes', 'product-helium-addon-for-woocommerce'),
			'display' => __('Yes', 'product-helium-addon-for-woocommerce'),
		);

		return $item_data;
	}

	/**
	 * Adjust price after adding to cart
	 *
	 * @access public
	 * @param mixed $cart_item array of cart item data.
	 * @return array array of cart item data
	 */
	public function add_cart_item($cart_item)
	{
		if (empty($cart_item['helium_add'])) {
			return $cart_item;
		}

		$cost = get_post_meta($cart_item['product_id'], '_helium_addon_cost', true);

		if ('' === $cost) {
			$cost = $this->helium_addon_cost;
		}

		$product = wc_get_product($cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id']);

		$cart_item['data']->set_price($product->get_price() + $this->get_price_in_currency($cost));
		// set weiight 500g
		$cart_item['data']->set_weight(500);

		return $cart_item;
	}

	/**
	 * After ordering, add the data to the order line items.
	 *
	 * @access public
	 * @param mixed $item_id ID of the item.
	 * @param mixed $cart_item cart item data.
	 * @return void
	 */
	public function add_order_item_meta($item_id, $cart_item)
	{
		if (empty($cart_item['helium_add'])) {
			return;
		}

		wc_add_order_item_meta($item_id, __('Helium Added', 'product-helium-addon-for-woocommerce'), __('Yes', 'product-helium-addon-for-woocommerce'));
	}

	/**
	 * Add Helium Addon option to general product edit box.
	 *
	 * @access public
	 * @return void
	 */
	public function write_panel()
	{
		global $post;

		echo '</div><div class="options_group show_if_simple show_if_variable">';

		$is_addable = get_post_meta($post->ID, '_is_helium_addable', true);

		if ('' === $is_addable && $this->helium_addon_enabled) {
			$is_addable = 'yes';
		}

		woocommerce_wp_checkbox(array(
			'id'            => '_is_helium_addable',
			'wrapper_class' => '',
			'value'         => $is_addable,
			'label'         => __('Helium addable', 'product-helium-addon-for-woocommerce'),
			'description'   => __('Enable this option if the customer can add helium.', 'product-helium-addon-for-woocommerce'),
		));

		woocommerce_wp_text_input(array(
			'id'          => '_helium_addon_cost',
			'label'       => __('Helium addon cost', 'product-helium-addon-for-woocommerce'),
			'placeholder' => $this->helium_addon_cost,
			'desc_tip'    => true,
			'description' => __('Override the default cost by inputting a cost here.', 'product-helium-addon-for-woocommerce'),
		));

		wc_enqueue_js("
			jQuery('input#_is_helium_addable').change(function(){

				jQuery('._helium_addon_cost_field').hide();

				if ( jQuery('#_is_helium_addable').is(':checked') ) {
					jQuery('._helium_addon_cost_field').show();
				}

			}).change();
		");
	}

	/**
	 * Save helium addon values for the product.
	 *
	 * @access public
	 * @param mixed $post_id Product ID.
	 * @return void
	 */
	public function write_panel_save($post_id)
	{
		$_is_helium_addable = !empty($_POST['_is_helium_addable']) ? 'yes' : 'no';
		$_helium_addon_cost    = !empty($_POST['_helium_addon_cost']) ? wc_clean($_POST['_helium_addon_cost']) : '';
		$_helium_addon_cost	= str_replace(',', '.', $_helium_addon_cost);

		update_post_meta($post_id, '_is_helium_addable', $_is_helium_addable);
		update_post_meta($post_id, '_helium_addon_cost', $_helium_addon_cost);
	}

	/**
	 * Create the settings for the plugin.
	 *
	 * @access public
	 * @return array Plugin settings
	 */
	public function admin_settings()
	{
		// Init settings.
		$this->settings = array(
			array(
				'name' 		=> __('Helium Adding Enabled by Default?', 'product-helium-addon-for-woocommerce'),
				'desc' 		=> __('Enable this to allow helium adding for products by default.', 'product-helium-addon-for-woocommerce'),
				'id' 		=> 'product_helium_addon_enabled',
				'type' 		=> 'checkbox',
			),
			array(
				'name' 		=> __('Default Helium Addon Cost', 'product-helium-addon-for-woocommerce'),
				'desc' 		=> __('The cost of helium addon unless overridden per-product.', 'product-helium-addon-for-woocommerce'),
				'id' 		=> 'product_helium_addon_cost',
				'type' 		=> 'text',
				'desc_tip'  => true,
			),
			array(
				'name' 		=> __('Helium Addon Message', 'product-helium-addon-for-woocommerce'),
				'id' 		=> 'product_helium_addon_message',
				'desc' 		=> __('Note: <code>{price}</code> will be replaced with the helium addon cost.', 'product-helium-addon-for-woocommerce'),
				'type' 		=> 'text',
				'desc_tip'  => __('Label shown to the user on the frontend.', 'product-helium-addon-for-woocommerce'),
			),
		);

		return $this->settings;
	}

	/**
	 * Display plugin settings in the WooCommerce settings.
	 *
	 * @access public
	 * @return void
	 */
	public function display_admin_settings()
	{
		woocommerce_admin_fields($this->admin_settings());
	}

	/**
	 * Save the plugin settings.
	 *
	 * @access public
	 * @return void
	 */
	public function save_admin_settings()
	{
		woocommerce_update_options($this->admin_settings());
	}
}
