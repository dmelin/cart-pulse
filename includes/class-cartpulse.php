<?php

namespace CartPulse;

defined('ABSPATH') or die('No script kiddies please!');

class Plugin
{
    private static $instance = null;
    private $table;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'cartpulse_events';

        add_action('woocommerce_add_to_cart', [$this, 'record_add_to_cart'], 10, 6);
        add_shortcode('cartadds', [$this, 'shortcode_cart_adds']);

        if (get_option('cartpulse_auto_display') !== false) {
            add_action('woocommerce_single_product_summary', [$this, 'display_cart_adds'], 25);
            add_action('woocommerce_after_shop_loop_item', [$this, 'display_cart_adds'], 25);
        }

        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'front_styles']);
    }

    public static function activate()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cartpulse_events';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            product_id bigint UNSIGNED NOT NULL,
            product_count int NOT NULL DEFAULT 1,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            
            index (product_id),
            index (timestamp),
            index (product_id, timestamp)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function admin_menu()
    {
        add_menu_page(
            __('Cart Pulse', 'cart-pulse'),
            __('Cart Pulse', 'cart-pulse'),
            'manage_options',
            'cart-pulse',
            [$this, 'admin_page'],
            'dashicons-cart',
            6
        );
    }

    public function admin_scripts()
    {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
        wp_enqueue_script('cartpulse-admin', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery', 'chartjs'), null, true);
    }
    public function front_styles()
    {
        wp_enqueue_style('cartpulse-style', plugin_dir_url(__FILE__) . 'css/style.css');
    }

    public function admin_page()
    {
        require_once plugin_dir_path(__FILE__) . 'views/admin-page.php';
    }

    public function record_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $cart_item_data, $cart)
    {
        global $wpdb;

        // Log the add to cart event to wp error log
        error_log("Product ID {$product_id} added to cart with quantity {$quantity}");

        $wpdb->insert(
            $this->table,
            [
                'product_id' => $product_id,
                'product_count' => $quantity,
                'timestamp'  => current_time('mysql'),
            ]
        );

        // Purge data older than 1 year
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table} WHERE timestamp < %s",
                date('Y-m-d H:i:s', strtotime('-1 year'))
            )
        );
    }

    public function shortcode_cart_adds($atts)
    {
        global $wpdb;

        $atts = shortcode_atts(
            [
                'product_id' => get_the_ID(),
                'duration' => 24,
            ],
            $atts
        );

        $product_id = intval($atts['product_id']);
        $duration = intval($atts['duration']);

        $since = date('Y-m-d H:i:s', strtotime("-{$duration} hours"));

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE product_id = %d AND timestamp >= %s",
                $product_id,
                $since
            )
        );

        if (!$count) $count = 0;

        $output = $count > 0 ? sprintf(
            _n('%d cart add in the last %d hours', '%d cart adds in the last %d hour' . ($duration > 0 ? 's' : ''), $count, 'cart-pulse'),
            $count,
            $duration
        ) : sprintf(
            __('No cart adds in the last %d hours', 'cart-pulse'),
            $duration
        );

        return apply_filters('cartpulse_shortcode_output', $output, $count, $atts);
    }

    public function display_cart_adds()
    {
        global $product;

        // Make sure we have a valid product context
        if (!is_a($product, 'WC_Product')) {
            return;
        }

        $product_id = $product->get_id();
        $shortcode_output = do_shortcode("[cartadds product_id='{$product_id}']");

        // Only display if there's actual content
        if (!empty($shortcode_output)) {
            echo '<div class="cartpulse-display">' . $shortcode_output . '</div>';
        }
    }
}
