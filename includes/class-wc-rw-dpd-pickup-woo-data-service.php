<?php


/**
 * Class Wc_Rw_Dpd_Pickup_Woo_Data_Service
 *
 * Provides data related to WooCommerce shipping methods for use in the DPD Pickup plugin.
 */
class Wc_Rw_Dpd_Pickup_Woo_Data_Service {

    /**
     * Retrieves all shipping methods from WooCommerce shipping zones.
     *
     * - Iterates over all shipping zones and collects their active shipping methods.
     * - Each method is added to the result as an associative array where the key is
     *   the shipping method index and the value is its title.
     * - The "Default" zone is intentionally excluded as it has a different data structure
     *   and is not relevant for the DPD Pickup widget.
     *
     * @return array An associative array of shipping methods, where the keys are method indices and the values are titles.
     */
    public function wc_rw_get_shipping_methods(): array
    {
        $allowed_shipping_methods = [];

        $shipping_zones = WC_Shipping_Zones::get_zones();

        foreach ($shipping_zones as $shipping_zone) {
            $zone = new WC_Shipping_Zone($shipping_zone['zone_id']);
            $shipping_methods = $zone->get_shipping_methods();
            foreach ($shipping_methods as $shipping_method_index => $shipping_method) {
                $allowed_shipping_methods[$shipping_method_index]  = $shipping_method->title;
            }
        }
        return $allowed_shipping_methods;
    }

}