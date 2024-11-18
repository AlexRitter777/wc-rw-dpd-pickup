<?php

/**
 * Plugin Name: WooCommerce RW DPD Pick Up
 * Description: Adds a DPD Pick-Up Points widget to the checkout page, allowing customers to select a pick-up location. The selected pick-up point information is saved in the order meta and displayed on the order page.
 * Version: 1.0.0
 * Author: Alexej Bogačev (RAIN WOOLF s.r.o.)
 * Text Domain: wc-rw-dpd-pickup
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}


/**
 * Main class for the WooCommerce RW DPD Pick Up plugin.
 */
class Wc_Rw_Dpd_Pickup{

    const VERSION = '1.0.0';

    /**
     * Wc_Rw_Dpd_Pickup constructor.
     * Initializes the plugin by registering hooks.
     */
    public function __construct()
    {

        $this->register_hooks();
    }



    /**
     * Registers all necessary hooks for the plugin.
     */
    private function register_hooks()
    {
        // Load the text domain for translations
        add_action('plugins_loaded', [$this, 'wc_rw_load_text_domain']);

        // Initialize the plugin's main functionality
        add_action('plugins_loaded', [$this, 'wc_rw_initialize_plugin']);

        // Load scripts and styles common for entire plugin
        add_action('wp_enqueue_scripts', [$this, 'wc_rw_load_public_scripts']);

        // Register AJAX handlers for authenticated and non-authenticated users
        add_action('wp_ajax_wc_rw_set_pickup_point_data_action', [Wc_Rw_Dpd_Pickup_Ajax_Handler::class, 'wc_rw_set_pickup_point_data_action']);
        add_action('wp_ajax_nopriv_wc_rw_set_pickup_point_data_action', [Wc_Rw_Dpd_Pickup_Ajax_Handler::class, 'wc_rw_set_pickup_point_data_action']);
    }

    /**
     * Initializes the plugin.
     *
     * - Loads required classes for the plugin's functionality.
     * - Checks if WooCommerce is active; deactivates the plugin if not.
     * - Initializes the main functionality of the plugin.
     * - Handles deactivation if WooCommerce is deactivated.
     */
    public function wc_rw_initialize_plugin()
    {
        $this->wc_rw_load_classes();

        // Check if WooCommerce is active and compatible
        Wc_Rw_Woocommerce_Checker::check_initialization(plugin_basename(__FILE__));

        // Initialize the core plugin logic
        new Wc_Rw_Dpd_Pickup_Init();

        // Handle the case when WooCommerce is deactivated
        Wc_Rw_Woocommerce_Checker::handle_woocommerce_deactivation(plugin_basename(__FILE__));
    }


    /**
     * Loads required classes for the plugin's functionality.
     */
    private function wc_rw_load_classes()
    {
        require_once WP_PLUGIN_DIR . '/wc-rw-dpd-pickup/includes/class-wc-rw-dpd-pickup-init.php';
        require_once WP_PLUGIN_DIR . '/wc-rw-dpd-pickup/includes/class-wc-rw-dpd-pickup-debug.php';
        require_once WP_PLUGIN_DIR . '/wc-rw-dpd-pickup/includes/class-wc-rw-dpd-pickup-settings.php';
        require_once WP_PLUGIN_DIR . '/wc-rw-dpd-pickup/includes/class-wc-rw-dpd-pickup-woo-data-service.php';
        require_once WP_PLUGIN_DIR . '/wc-rw-dpd-pickup/includes/class-wc-rw-dpd-pickup-ajax-handler.php';
    }



    /**
     * Load public scripts and styles. Only for checkout page.
     */
    public function wc_rw_load_public_scripts(){

        if (is_checkout()) {

            // Load the main JavaScript file for the plugin
            wp_enqueue_script(
                'wc-rw-dpd-pickup-script',
                plugins_url('assets/js/main.js', __FILE__),
                array('jquery'),
                Wc_Rw_Dpd_Pickup::VERSION,
                true
            );

            // Load the main CSS file for the plugin
            wp_enqueue_style(
                'wc-rw-dpd-pickup-style',
                plugins_url('assets/css/style.css', __FILE__),
                array(),
                Wc_Rw_Dpd_Pickup::VERSION
            );

            // Localize script with AJAX URLs and error messages
            wp_localize_script(
                'wc-rw-dpd-pickup-script',
                'wc_rw_dpd_pickup_ajax_obj',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'security' => wp_create_nonce('wc_rw_dpd_pickup_ajax_nonce'),
                    'pickup_point_save_error' => __('Pickup point save attempt failed. Please try again later or select another shipping method.', 'wc-rw-dpd-pickup'),
                    'change_pickup_point' => __('Change pickup point', 'wc-rw-dpd-pickup')

                )
            );
        }
    }

    /**
     * Load the plugin text domain for translations.
     */
    public function wc_rw_load_text_domain() {
        // Load the text domain from the /languages directory
        load_plugin_textdomain('wc-rw-dpd-pickup', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }


}

/**
 * Initialize and return an instance of the main plugin class.
 *
 * @return Wc_Rw_Dpd_Pickup
 */
function wc_rw_dpd_pickup(): Wc_Rw_Dpd_Pickup
{
    return new Wc_Rw_Dpd_Pickup();
}

require_once WP_PLUGIN_DIR . '/wc-rw-dpd-pickup/includes/class-wc-rw-woocommerce-checker.php';
register_activation_hook(__FILE__, [Wc_Rw_Woocommerce_Checker::class, 'check_activation']);

// Start the plugin execution.
wc_rw_dpd_pickup();