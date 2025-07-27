<?php
global $wpdb;

$start = new DateTime('-1 day');
$end = new DateTime('now');

$labels = [];
$interval = new DateInterval('PT1H'); // 1 hour steps
$period = new DatePeriod($start, $interval, $end);

foreach ($period as $dt) {
    $labels[] = $dt->format('Y-m-d H:00:00'); // keep full timestamp for keys/indexes
}

$sql = "SELECT 
    product_id,
    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') AS hour,
    COUNT(*) AS count
FROM {$this->table}
WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY product_id, hour
ORDER BY hour ASC;";

$results = $wpdb->get_results($sql, ARRAY_A);

if (empty($results)) {
    echo '<p>' . esc_html__('No cart adds recorded in the last 30 days.', 'cart-pulse') . '</p>';
} else {
    $productNames = [];
    foreach ($results as $row) {
        if (!isset($productNames[$row['product_id']])) {
            $productNames[$row['product_id']] = get_the_title($row['product_id']);
        }
    }

    $colors = [];
    $hueStep = 360 / count($productNames);

    for ($i = 0; $i < count($productNames); $i++) {
        $hue = $i * $hueStep;
        $saturation = 70;
        $lightness = 50;
        $colors[] = "hsl($hue, {$saturation}%, {$lightness}%)";
    }

    $datasets = [];
    foreach ($productNames as $product_id => $productName) {
        $datasets[$product_id] = [
            'label' => $productName,
            'data' => array_fill(0, count($labels), 0),
            'backgroundColor' => $colors[$product_id % count($colors)],
            'borderColor' => $colors[$product_id % count($colors)],
            'borderWidth' => 1,
            'tension' => 0.5,
            'image' => "content here",
        ];
    }

    foreach ($results as $row) {
        $hourIndex = array_search($row['hour'], $labels);
        if ($hourIndex !== false) {
            $datasets[$row['product_id']]['data'][$hourIndex] = (int)$row['count'];
        }
    }

    $data = array_values($datasets);
}
?>
<script>
    cartAddLabels = <?php echo json_encode($labels); ?>;
    cartAddData = <?php echo json_encode($data); ?>;
</script>
<div class="wrap">
    <h1><?php esc_html_e('Cart Pulse', 'cart-pulse'); ?></h1>
    <p><?php esc_html_e('This plugin tracks products added to the cart and displays statistics.', 'cart-pulse'); ?></p>
    <p><?php esc_html_e('Use the shortcode [cartadds] to display cart adds on your site.', 'cart-pulse'); ?></p>
    <hr>

    <!-- Display a graph for cart adds -->
    <h2><?php esc_html_e('Cart Adds Graph', 'cart-pulse'); ?></h2>
    <div id="cart-adds-graph">
        <!-- Graph area with JS to render the graph -->

        <div id="cart-adds-graph-container">
            <canvas id="cartpulse" style="width: 100%; height: 600px; background-color: #fff;"></canvas>
        </div>
    </div>
</div>