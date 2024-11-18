<?php

class Wc_Rw_Dpd_Pickup_Ajax_Handler {

    /**
     * Handles the AJAX request to save DPD pickup point data to the WooCommerce session.
     *
     * - Validates the AJAX nonce for security.
     * - Checks if the required pickup point data is provided in the request.
     * - Saves the data to the WooCommerce session if validation passes.
     * - Sends a JSON response indicating success or failure.
     *
     * @return void
     */
    public static function wc_rw_set_pickup_point_data_action(){

        // Ensure the request is coming from an authorized source
        check_ajax_referer('wc_rw_dpd_pickup_ajax_nonce', 'security');
        if(
           !empty($_POST['pickup_point_data']) &&
           !empty($_POST['pickup_point_data']['name']) &&
           !empty($_POST['pickup_point_data']['id']) &&
           !empty($_POST['pickup_point_data']['location']) &&
           !empty($_POST['pickup_point_data']['pickupPointResult'])
           )
        {
            $pickup_point_data = $_POST['pickup_point_data'];
            WC()->session->set('wc_rw_dpd_pickup_point_data', $pickup_point_data);
            wp_send_json_success();
        }
        wp_send_json_error('No data received.');
    }
}