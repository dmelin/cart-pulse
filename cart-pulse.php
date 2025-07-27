<?php

/**
 * Plugin Name: Cart Pulse
 * Description: A plugin that adds stats for carts
 * Version: 1.0.0
 * Author: Daniel Melin
 * License: GPL2
 *
 */

defined('ABSPATH') or die('No script kiddies please!');

require_once plugin_dir_path(__FILE__) . 'includes/class-cartpulse.php';

function cartpulse()
{
    return \CartPulse\Plugin::instance();
}

add_action('plugins_loaded', 'cartpulse');

// Activate the plugin
register_activation_hook(__FILE__, ['\CartPulse\Plugin', 'activate']);

// Deactivate the plugin
register_deactivation_hook(__FILE__, function () {
    global $wpdb;
    $table = $wpdb->prefix . 'cartpulse_events';
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
});
