<?php
/**
 * فایل مدیریت ضایعات
 * نمایش ضایعات تولید
 */

function waste_management_page() {
    if (!current_user_can('edit_posts')) {
        wp_die('<p>شما دسترسی به این بخش را ندارید.</p>');
    }

    global $wpdb;
    ob_start();
    ?>
    <div class="wrap">
        <h1>مدیریت ضایعات</h1>
        <form method="get">
            <input type="hidden" name="page" value="waste-management">
            <label>فیلتر بر اساس سری ساخت:
                <input type="text" name="serial_code" value="<?php echo isset($_GET['serial_code']) ? esc_attr($_GET['serial_code']) : ''; ?>">
            </label>
            <input type="submit" value="فیلتر">
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>سری ساخت</th>
                    <th>ضایعات مواد اولیه</th>
                    <th>ضایعات ملزومات بسته‌بندی</th>
                    <th>تاریخ ثبت</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $where = '';
                if (isset($_GET['serial_code']) && !empty($_GET['serial_code'])) {
                    $serial_code = sanitize_text_field($_GET['serial_code']);
                    $where = $wpdb->prepare(" WHERE serial_code LIKE %s", '%' . $wpdb->esc_like($serial_code) . '%');
                }

                $wastes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}waste $where ORDER BY created_at DESC");
                foreach ($wastes as $waste) {
                    $waste_details = json_decode($waste->waste_details, true);

                    $material_wastes = [];
                    if (!empty($waste_details['materials'])) {
                        foreach ($waste_details['materials'] as $index => $material_id) {
                            $material_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}materials WHERE id = %d", $material_id));
                            if ($material_data) {
                                $material_wastes[] = $material_data->name . ': ' . $waste_details['quantities'][$index];
                            }
                        }
                    }

                    $packaging_wastes = [];
                    if (!empty($waste_details['packaging'])) {
                        foreach ($waste_details['packaging'] as $index => $pack_id) {
                            $pack_data = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}packaging WHERE id = %d", $pack_id));
                            if ($pack_data) {
                                $packaging_wastes[] = $pack_data->name . ': ' . $waste_details['packaging_quantities'][$index];
                            }
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo esc_html($waste->serial_code); ?></td>
                        <td><?php echo esc_html(implode(', ', $material_wastes)); ?></td>
                        <td><?php echo esc_html(implode(', ', $packaging_wastes)); ?></td>
                        <td><?php echo esc_html($waste->created_at); ?></td>
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