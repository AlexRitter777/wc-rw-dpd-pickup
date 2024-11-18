<?php


class Wc_Rw_Woocommerce_Checker {

    public static string $plugin_text_domain = 'wc-rw-dpd-pickup';

    /**
     * Checks if WooCommerce is active during plugin activation.
     *
     * @throws Exception If WooCommerce is not active.
     */
    public static function check_activation() {
        if (!is_plugin_active('woocommerce/woocommerce.php') && !class_exists('WooCommerce')) {
            wp_die(
                __('WooCommerce is required for this plugin to work. Please install and activate WooCommerce.', self::$plugin_text_domain ),
                __('Plugin dependency check failed', self::$plugin_text_domain),
                array('back_link' => true)
            );
        }
    }

    /**
     * Validates that WooCommerce is active during plugin initialization.
     *
     * @param string $plugin_file The plugin file path for deactivation, if needed.
     */
    public static function check_initialization($plugin_file) {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function () {
                ?>
                <div class="error">
                    <p><?php _e('This plugin requires WooCommerce to be active. Please activate WooCommerce first.', self::$plugin_text_domain); ?></p>
                </div>
                <?php
            });

            // Deactivate the plugin if WooCommerce is not active.
            deactivate_plugins($plugin_file);
        }
    }

    /**
     * Validates the minimum WooCommerce version.
     *
     * @param string $required_version The minimum required WooCommerce version.
     */
    public static function check_version($required_version) {
        if (class_exists('WooCommerce')) {
            global $woocommerce;

            if (version_compare($woocommerce->version, $required_version, '<')) {
                add_action('admin_notices', function () use ($required_version) {
                    ?>
                    <div class="error">
                        <p><?php printf(__('This plugin requires WooCommerce version %s or higher. Please update WooCommerce.', self::$plugin_text_domain), $required_version); ?></p>
                    </div>
                    <?php
                });
            }
        }
    }

    /**
     * Handles the deactivation of the plugin if WooCommerce is deactivated.
     *
     * @param string $plugin_file The plugin file path for deactivation.
     */
    public static function handle_woocommerce_deactivation($plugin_file) {
        add_action('deactivated_plugin', function ($plugin) use ($plugin_file) {
            if ($plugin === 'woocommerce/woocommerce.php') {
                deactivate_plugins($plugin_file);
                add_action('admin_notices', function () {
                    ?>
                    <div class="error">
                        <p><?php _e('This plugin has been deactivated because WooCommerce was deactivated.', self::$plugin_text_domain); ?></p>
                    </div>
                    <?php
                });
            }
        });
    }
}
