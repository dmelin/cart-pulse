# cart-pulse
Adds a cart item tracker and statistics

## how
The plugin hooks into the WC events `woocommerce_add_to_cart` and `woocommerce_after_shop_loop_item`. The plugin then logs what product was added to a cart and when

### database
The plugin creates a custom table to keep track of its data, the table is deleted when the plugin is deactivated

## the WHY
The plugin allows for an up-sell feature for e-commerce websites using WooCommerce without adding any extra work for the admins. The plugin is self maintained and automated.

The plugin has support to display data with variant durations using the shortcode and parameters. It can also show data for a specific product.

The admin page can hold settings (like "automaticaly add cart stats to product pages) - plugin code already checks for this from the options table (`cartpulse_auto_display`: `true` manually added for now), option to set what the default duration should be and many more things.

Later features to the plugin could be to add a custom css editor so that users can style the box without editing the theme files.