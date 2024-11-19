<?php

// If this file is called directly, abort.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Option name to be deleted
$option_name = 'wc_rw_dpd_pickup_shipping_method';

// Delete the option
delete_option($option_name);