<?php
/**
 * فایل گزارش تولید
 * نمایش گزارش‌های تولید
 */

function serial_production_report_page() {
    // شرط دسترسی را تغییر می‌دهیم تا کاربران با نقش‌های دیگر هم بتوانند گزارش را ببینند
    if (!current_user_can('edit_posts')) { // به جای manage_options از edit_posts استفاده می‌کنیم
        wp_die('<p>شما دسترسی به این بخش را ندارید.</p>');
    }

    global $wpdb;
    ob_start();
    ?>
    <div class="wrap">
        <h1>گزارش تولید</h1>
        <form method="get">
            <input type="hidden" name="page" value="serial-report">
            <label>فیلتر بر اساس سری ساخت:
                <input type="text" name="serial_code" value="<?php echo isset($_GET['serial_code']) ? esc_attr($_GET['serial_code']) : ''; ?>">
            </label>
            <input type="submit" value="فیلتر">
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>سری ساخت</th>
                    <th>کاربر</th>
                    <th>کارگران</th>
                    <th>ماشین‌آلات</th>
                    <th>فرآیندها</th>
                    <th>مواد اولیه</th>
                    <th>ملزومات بسته‌بندی</th>
                    <th>محصولات</th>
                    <th>ضایعات</th>
                    <th>اقدامات عمومی</th>
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
                    $user = get_userdata($production->user_id);
                    $workers = json_decode($production->workers, true);
                    $machines = json_decode($production->machines, true);
                    $processes = json_decode($production->processes, true);
                    $materials = json_decode($production->materials, true);
                    $packaging = json_decode($production->packaging, true);
                    $products = json_decode($production->products, true);
                    $waste = json_decode($production->waste, true);
                    $general_actions = json_decode($production->general_actions, true);

                    $worker_names = [];
                    if (is_array($workers)) {
                        foreach ($workers as $worker) {
                            $worker_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}workers WHERE id = %d", $worker['id']));
                            if ($worker_data) {
                                $worker_names[] = $worker_data->name;
                            }
                        }
                    }

                    $machine_names = [];
                    if (is_array($machines)) {
                        foreach ($machines as $machine) {
                            $machine_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}machines WHERE id = %d", $machine['id']));
                            if ($machine_data) {
                                $machine_names[] = $machine_data->name;
                            }
                        }
                    }

                    $process_names = [];
                    if (is_array($processes)) {
                        foreach ($processes as $process) {
                            $process_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}processes WHERE id = %d", $process['id']));
                            if ($process_data) {
                                $process_names[] = $process_data->name;
                            }
                        }
                    }

                    $material_details = [];
                    if (is_array($materials)) {
                        foreach ($materials as $material) {
                            $material_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}materials WHERE id = %d", $material['id']));
                            if ($material_data) {
                                $material_details[] = $material_data->name . ': ' . $material['quantity'] . (isset($material['waste']) ? ' (ضایعات: ' . $material['waste'] . ')' : '');
                            }
                        }
                    }

                    $packaging_details = [];
                    if (is_array($packaging)) {
                        foreach ($packaging as $pack) {
                            $pack_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}packaging WHERE id = %d", $pack['id']));
                            if ($pack_data) {
                                $packaging_details[] = $pack_data->name . ': ' . $pack['quantity'] . (isset($pack['waste']) ? ' (ضایعات: ' . $pack['waste'] . ')' : '');
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

                    $waste_details = [];
                    if (is_array($waste)) {
                        if (!empty($waste['materials'])) {
                            foreach ($waste['materials'] as $index => $material_id) {
                                $material_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}materials WHERE id = %d", $material_id));
                                if ($material_data) {
                                    $waste_details[] = 'ماده اولیه: ' . $material_data->name . ' - ' . $waste['quantities'][$index];
                                }
                            }
                        }
                        if (!empty($waste['packaging'])) {
                            foreach ($waste['packaging'] as $index => $pack_id) {
                                $pack_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}packaging WHERE id = %d", $pack_id));
                                if ($pack_data) {
                                    $waste_details[] = 'ملزوم بسته‌بندی: ' . $pack_data->name . ' - ' . $waste['packaging_quantities'][$index];
                                }
                            }
                        }
                    }

                    $action_names = [];
                    if (is_array($general_actions)) {
                        foreach ($general_actions as $action) {
                            $action_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}general_actions WHERE id = %d", $action['id']));
                            if ($action_data) {
                                $action_names[] = $action_data->name;
                            }
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo esc_html($production->serial_code); ?></td>
                        <td><?php echo $user ? esc_html($user->display_name) : '-'; ?></td>
                        <td><?php echo esc_html(implode(', ', $worker_names)); ?></td>
                        <td><?php echo esc_html(implode(', ', $machine_names)); ?></td>
                        <td><?php echo esc_html(implode(', ', $process_names)); ?></td>
                        <td><?php echo esc_html(implode(', ', $material_details)); ?></td>
                        <td><?php echo esc_html(implode(', ', $packaging_details)); ?></td>
                        <td><?php echo esc_html(implode(', ', $product_details)); ?></td>
                        <td><?php echo esc_html(implode(', ', $waste_details)); ?></td>
                        <td><?php echo esc_html(implode(', ', $action_names)); ?></td>
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