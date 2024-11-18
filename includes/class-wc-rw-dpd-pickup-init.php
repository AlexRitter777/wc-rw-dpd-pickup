<?php

/**
 * Class Wc_Rw_Dpd_Pickup_Init
 *
 * Initializes and manages the core functionality of the WooCommerce RW DPD Pickup plugin.
 *
 * Responsibilities:
 * - Registers hooks to integrate the DPD Pickup widget into the WooCommerce checkout process.
 * - Validates the selection of a DPD pickup point during checkout.
 * - Updates order shipping details with the selected DPD pickup point data.
 * - Provides settings and data attributes for configuring the DPD Pickup widget.
 *
 * Key Features:
 * - Adds settings for DPD Pickup in both the plugins page and WooCommerce admin.
 * - Integrates a widget link on the checkout page for the selected DPD shipping method.
 * - Validates and ensures a pickup point is selected before completing the order.
 * - Saves the selected pickup point in the WooCommerce session and updates the order.
 */
class Wc_Rw_Dpd_Pickup_Init
{

    public function __construct()
    {
        // Initialize settings
        $this->wc_rw_init_settings();

        // Adds a settings link to the plugins page.
        add_filter( "plugin_action_links", [$this, 'wc_rw_create_settings_link'], 10, 2);

        // Outputs a hidden HTML element with the DPD pickup shipping method as a data attribute.
        add_action('woocommerce_after_checkout_form', [$this, 'wc_rw_output_dpd_pickup_option_data_attribute']);

        // Adds the DPD pickup widget link to the checkout page for the selected shipping method.
        add_action('woocommerce_after_shipping_rate', [$this, 'wc_rw_dpd_pickup_add_widget_link'], 10, 2);

        // Validates that a DPD pickup point was selected during checkout.
        add_action('woocommerce_after_checkout_validation', [$this, 'wp_rw_dpd_pickup_validate_pickup_point'], 10, 2);

        // Updates the shipping data in the order with the DPD pickup point information.
        add_action('woocommerce_checkout_create_order', [$this, 'wc_rw_dpd_pickup_update_shipping_data'], 10, 2);

    }


    /**
     * Adds a settings link to the plugins page.
     *
     * @param array $plugin_actions Existing plugin actions.
     * @param string $plugin_file The current plugin file.
     * @return array Modified plugin actions.
     */
    public function wc_rw_create_settings_link ($plugin_actions, $plugin_file){
        $new_actions = array();
        if ( plugin_basename( dirname( __DIR__ )) . '/wc-rw-dpd-pickup.php' === $plugin_file ) {
            $new_actions['cl_settings'] = sprintf(  '<a href="%s">'. __('Settings', 'wc-rw-dpd-pickup' ) . '</a>',  esc_url( admin_url( 'options-general.php?page=wc-rw-dpd-pickup' ) ) );
        }
        return array_merge( $new_actions, $plugin_actions );
    }


    /**
     * Outputs a hidden HTML element with the DPD pickup shipping method as a data attribute.
     */
    public function wc_rw_output_dpd_pickup_option_data_attribute(){
        $shipping_method_option = get_option('wc_rw_dpd_pickup_shipping_method', 'disabled');
        echo '<div id="dpd-pickup-data" data-shipping-method="' . esc_attr($shipping_method_option) . '" style="display: none;"></div>';
    }


    /**
     * Adds the DPD pickup widget link to the checkout page for the selected shipping method.
     *
     * - Outputs the "Select pickup point" link or the selected pickup point information.
     * - Displays a validation error class if there was a previous validation error in the session.
     * - Ensures the widget is added only if the current shipping method matches the DPD pickup option.
     *
     * @param object $method The shipping method object.
     * @param int $index The index of the shipping method.
     */
    public function wc_rw_dpd_pickup_add_widget_link($method, $index){
        if(!is_checkout()) return;

        $widget_shipping_method =  get_option('wc_rw_dpd_pickup_shipping_method', '');

        $chosen_method_index = $this->get_selected_method_shipping_index();

        if($method->instance_id == $widget_shipping_method && $chosen_method_index == $widget_shipping_method){
            $error_class ='';

            if(WC()->session->get('wc_rw_dpd_pickup_point_data_validation_error')){
                $error_class = 'wc-rw-dpd-pickup-validation-error';
                WC()->session->__unset('wc_rw_dpd_pickup_point_data_validation_error');
            }

            echo "</br><div id='wc-rw-dpd-pickup-select-point-link-wrapper'>";
            if(!empty($pickup_point_data = WC()->session->get('wc_rw_dpd_pickup_point_data'))){
                echo "<div class='wc-rw-dpd-pickup-point-info'>";
                echo "<span>${pickup_point_data['name']}</span></br>";
                echo "<span>${pickup_point_data['pickupPointResult']}</span></br>";
                echo "<a class='wc-rw-dpd-pickup-select-point-link' id='wc-rw-dpd-pickup-select-point-link'>Change pickup point</a>";
                echo "</div>";
            }else{
                echo "<a class='wc-rw-dpd-pickup-select-point-link $error_class' id='wc-rw-dpd-pickup-select-point-link'> " . __('Select pickup point', 'wc-rw-dpd-pickup') . "</a>";
            }
            echo "</div>";
        }

    }

    /**
     * Retrieves the index of the currently selected shipping method.
     *
     * - Extracts the shipping method index from the chosen shipping method in the WooCommerce session.
     * - Returns an empty value if no shipping method is selected or the format is invalid.
     *
     * @return string The shipping method index or an empty string.
     */
    private function get_selected_method_shipping_index(){
        $chosen_method = isset( WC()->session->chosen_shipping_methods[0] ) ? WC()->session->chosen_shipping_methods[0] : '';
        $chosen_method_exploded = explode(':', $chosen_method);
        return $chosen_method_exploded[1];
    }



    /**
     * Initializes the plugin settings by creating required service and settings objects.
     *
     * - Instantiates the Wc_Rw_Dpd_Pickup_Woo_Data_Service.
     * - Passes the service instance to the Wc_Rw_Dpd_Pickup_Settings constructor.
     */
    private function wc_rw_init_settings() {
        $wc_rw_woo_data_service = new Wc_Rw_Dpd_Pickup_Woo_Data_Service();
        new Wc_Rw_Dpd_Pickup_Settings($wc_rw_woo_data_service);
    }

    /**
     * Validates that a DPD pickup point was selected during checkout.
     *
     * @param array $data The checkout data.
     * @param WP_Error $errors The error object to collect validation errors.
     */
    public function wp_rw_dpd_pickup_validate_pickup_point($data, $errors){

        $widget_shipping_method =  get_option('wc_rw_dpd_pickup_shipping_method', '');
        $chosen_method_index =  $this->get_selected_method_shipping_index();

        if($widget_shipping_method == $chosen_method_index && empty(WC()->session->get('wc_rw_dpd_pickup_point_data'))){
            $errors->add('delivery_point_missing', __('Please select a pickup point before completing your order.', 'wc-rw-dpd-pickup'));
            WC()->session->set('wc_rw_dpd_pickup_point_data_validation_error', true);
        }
    }

    /**
     * Updates the shipping data in the order with the DPD pickup point information.
     *
     * @param WC_Order $order The WooCommerce order object being processed.
     * @param array $data The checkout data submitted by the customer.
     * @throws WC_Data_Exception
     */
    public function wc_rw_dpd_pickup_update_shipping_data($order, $data){

        $widget_shipping_method =  get_option('wc_rw_dpd_pickup_shipping_method', '');
        $chosen_method_index =  $this->get_selected_method_shipping_index();
        if($widget_shipping_method == $chosen_method_index && !empty(WC()->session->get('wc_rw_dpd_pickup_point_data'))){
            $pickup_point_data = WC()->session->get('wc_rw_dpd_pickup_point_data');
            $order->set_shipping_first_name(__('Pickup Point: ', 'wc-rw-dpd-pickup'));
            $order->set_shipping_last_name($pickup_point_data['id']);
            $order->set_shipping_address_1($pickup_point_data['name']);
            $order->set_shipping_address_2($pickup_point_data['location']['address']['street']);
            $order->set_shipping_city($pickup_point_data['location']['address']['city']);
            $order->set_shipping_postcode($pickup_point_data['location']['address']['zip']);
            $order->set_shipping_country($pickup_point_data['location']['address']['country']);
            WC()->session->__unset('wc_rw_dpd_pickup_point_data');
        }
    }

}