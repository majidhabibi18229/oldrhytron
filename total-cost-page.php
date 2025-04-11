<?php
/**
 * فایل قیمت تمام‌شده
 * نمایش قیمت تمام‌شده تولید
 */

function serial_total_cost_page() {
    if (!current_user_can('edit_posts')) {
        wp_die('<p>شما دسترسی به این بخش را ندارید.</p>');
    }

    global $wpdb;
    ob_start();
    ?>
    <div class="wrap">
        <h1>قیمت تمام‌شده</h1>
        <form method="get">
            <input type="hidden" name="page" value="serial-total-cost">
            <label>فیلتر بر اساس سری ساخت:
                <input type="text" name="serial_code" value="<?php echo isset($_GET['serial_code']) ? esc_attr($_GET['serial_code']) : ''; ?>">
            </label>
            <input type="submit" value="فیلتر">
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>سری ساخت</th>
                    <th>مواد اولیه</th>
                    <th>ملزومات بسته‌بندی</th>
                    <th>محصولات</th>
                    <th>هزینه کل</th>
                    <th>تاریخ تولید</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $where = '';
                if (isset($_GET['serial_code']) && !empty($_GET['serial_code'])) {
                    $serial_code = sanitize_text_field($_GET['serial_code']);
                    $where = $wpdb->prepare(" WHERE serial_code LIKE %s", '%' . $wpdb->esc_like($serial_code) . '%');
                }

                $productions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}production_data $where ORDER BY production_date DESC");
                foreach ($productions as $production) {
                    $materials = json_decode($production->materials, true);
                    $packaging = json_decode($production->packaging, true);
                    $products = json_decode($production->products, true);

                    $material_details = [];
                    if (is_array($materials)) {
                        foreach ($materials as $material) {
                            $material_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}materials WHERE id = %d", $material['id']));
                            $price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}material_prices WHERE id = %d", $material['price_id']));
                            if ($material_data) {
                                $material_details[] = $material_data->name . ': ' . $material['quantity'] . ' (هزینه: ' . number_format($price * $material['quantity']) . ')';
                            }
                        }
                    }

                    $packaging_details = [];
                    if (is_array($packaging)) {
                        foreach ($packaging as $pack) {
                            $pack_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}packaging WHERE id = %d", $pack['id']));
                            $price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}packaging_prices WHERE id = %d", $pack['price_id']));
                            if ($pack_data) {
                                $packaging_details[] = $pack_data->name . ': ' . $pack['quantity'] . ' (هزینه: ' . number_format($price * $pack['quantity']) . ')';
                            }
                        }
                    }

                    $product_details = [];
                    if (is_array($products)) {
                        foreach ($products as $product) {
                            $product_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}products WHERE id = %d", $product['id']));
                            if ($product_data) {
                                $product_details[] = $product_data->name . ': ' . $product['quantity'];
                            }
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo esc_html($production->serial_code); ?></td>
                        <td><?php echo esc_html(implode(', ', $material_details)); ?></td>
                        <td><?php echo esc_html(implode(', ', $packaging_details)); ?></td>
                        <td><?php echo esc_html(implode(', ', $product_details)); ?></td>
                        <td><?php echo number_format($production->total_cost); ?></td>
                        <td><?php echo esc_html($production->production_date); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
    echo ob_get_clean();
}