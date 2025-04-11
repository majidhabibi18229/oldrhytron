<?php
/**
 * فایل مدیریت دلایل
 * امکان ثبت و مدیریت دلایل برای ورود و خروج انبار
 */

function serial_production_reasons_page() {
    if (!current_user_can('manage_options')) {
        wp_die('<p>شما دسترسی به این بخش را ندارید.</p>');
    }

    global $wpdb;
    ob_start();
    $message = '';

    if (isset($_POST['add_reason'])) {
        $reason = sanitize_text_field($_POST['reason']);
        $type = sanitize_text_field($_POST['type']);
        $wpdb->insert($wpdb->prefix . 'production_reasons', [
            'reason' => $reason,
            'type' => $type
        ]);
        $message = '<p>دلیل با موفقیت اضافه شد.</p>';
    }

    if (isset($_GET['delete_reason'])) {
        $id = intval($_GET['id']);
        $wpdb->delete($wpdb->prefix . 'production_reasons', ['id' => $id]);
        $message = '<p>دلیل با موفقیت حذف شد.</p>';
    }

    $reasons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}production_reasons");
    ?>
    <div class="wrap">
        <h1>مدیریت دلایل</h1>
        <?php if ($message) echo $message; ?>
        <table class="wp-list-table widefat fixed striped">
            <tr>
                <th>شناسه</th>
                <th>دلیل</th>
                <th>نوع</th>
                <th>عملیات</th>
            </tr>
            <?php if ($reasons) {
                foreach ($reasons as $reason) { ?>
                    <tr>
                        <td><?php echo esc_html($reason->id); ?></td>
                        <td><?php echo esc_html($reason->reason); ?></td>
                        <td><?php echo $reason->type == 'add' ? 'ورود' : 'خروج'; ?></td>
                        <td>
                            <a href="?page=serial-production-reasons&delete_reason=1&id=<?php echo $reason->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                        </td>
                    </tr>
                <?php }
            } ?>
        </table>

        <h2>افزودن دلیل جدید</h2>
        <form method="post">
            <label>دلیل:
                <input type="text" name="reason" required>
            </label>
            <label>نوع:
                <select name="type" required>
                    <option value="add">ورود</option>
                    <option value="remove">خروج</option>
                </select>
            </label>
            <input type="submit" name="add_reason" value="افزودن">
        </form>
    </div>
    <?php
    echo ob_get_clean();
}