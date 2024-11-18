<?php


/**
 * Class Wc_Rw_Dpd_Pickup_Settings
 *
 * Handles the settings for the WooCommerce RW DPD Pick Up plugin.
 *
 * Responsibilities:
 * - Registers a settings page for managing the DPD widget configuration.
 * - Provides a UI for selecting shipping methods compatible with the DPD widget.
 * - Stores plugin settings in the WordPress database.
 * - Removes the default settings menu from the WordPress "Options" page.
 *
 * Dependencies:
 * - Wc_Rw_Dpd_Pickup_Woo_Data_Service: Provides shipping methods data for the plugin.
 */
class Wc_Rw_Dpd_Pickup_Settings {

    private Wc_Rw_Dpd_Pickup_Woo_Data_Service $data_service;

    public function __construct(Wc_Rw_Dpd_Pickup_Woo_Data_Service $data_service)
    {
        $this->data_service = $data_service;
        add_action( 'admin_menu', [$this, 'wc_rw_register_settings_page'] );
        add_action( 'admin_init', [$this, 'wc_rw_remove_settings_menu'], 99);
        add_action( 'admin_init', [$this, 'wc_rw_register_settings'] );
    }

    /**
     * Registers a settings page for the plugin without adding it to the admin menu.
     */
    public function wc_rw_register_settings_page() : void {
        add_options_page(
            __('WC RW Dpd Pickup Settings', 'wc-rw-dpd-pickup'), // Settings page title
            __('DPD Pickup', 'wc-rw-dpd-pickup'), // Menu title
            'manage_options', // permission
            'wc-rw-dpd-pickup', // settings page URL
            [$this, 'wc_rw_render_settings_page']
        );

        add_menu_page(
            __('WC RW Dpd Pickup Settings', 'wc-rw-dpd-pickup'), // Settings page title
            __('DPD Pickup', 'wc-rw-dpd-pickup'),  // Menu title
            'manage_options', // permission
            'wc-rw-dpd-pickup', // settings page URL
            [$this, 'wc_rw_render_settings_page'], // Callback
            'dashicons-admin-generic', // Menu icon (Dashicons)
            25 // Position in menu
        );
    }


    /**
     * Registers all settings fields for the plugin.
     * It registers settings for altering shipping and COD names and company data (bank details).
     */
    public function wc_rw_register_settings() : void {
        register_setting( 'wc_rw_dpd_pickup_settings', 'wc_rw_dpd_pickup_shipping_method' );

        add_settings_section(
            'wc_rw_dpd_pickup_section_shipping_methods',
            __('Select shipping method for DPD Widget', 'wc-rw-dpd-pickup'),
            '',
            'wc-rw-dpd-pickup'
        );

        add_settings_field(
            'wc_rw_dpd_pickup_shipping_method',
            __('Allowed shipping methods for DPD widget:', 'wc-rw-dpd-pickup'),
            [$this, 'wc_rw_render_settings_field'],
            'wc-rw-dpd-pickup',
            'wc_rw_dpd_pickup_section_shipping_methods',

        );

    }


    /**
     * Renders the settings page for the plugin.
     */
    public function wc_rw_render_settings_page() : void {
        ?>
        <div class="wrap">
            <h1><?php _e('WooCommerce RW DPD PickUp Settings', 'wc-rw-dpd-pickup'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'wc_rw_dpd_pickup_settings' );
                do_settings_sections( 'wc-rw-dpd-pickup' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }


    /**
     * Renders the settings field input for the settings page.
     *
     * @param array $args Arguments containing the input name.
     */
    public function wc_rw_render_settings_field(array $args) : void {

        $shipping_methods = $this->data_service->wc_rw_get_shipping_methods();
        $value = get_option( 'wc_rw_dpd_pickup_shipping_method');

        echo "<label>";
        echo "<input type='radio' name='wc_rw_dpd_pickup_shipping_method' value='disabled' ". checked($value, 'disabled', false) . ">";
        echo "DPD widget disabled";
        echo "</label><br>";

        if($shipping_methods){
            foreach ($shipping_methods as $index => $method) {
                echo "<label>";
                echo "<input type='radio' name='wc_rw_dpd_pickup_shipping_method' value='$index' " . checked($value, $index, false) . ">";
                echo  $method . "<br>";
                echo "</label>";
            }
        }else{
            echo "<p>No shipping methods are available for DPD widget. Please configure shipping methods in WooCommerce.</p>";
        }
    }

    /**
     * Removes the plugin's settings page link from the "Settings" submenu.
     */
    public function wc_rw_remove_settings_menu() {
        global $submenu;

        if (isset($submenu['options-general.php'])) {
            foreach ($submenu['options-general.php'] as $index => $item) {
                if ($item[2] === 'wc-rw-dpd-pickup') { // Compare page url
                    unset($submenu['options-general.php'][$index]);
                }
            }
        }
    }
}