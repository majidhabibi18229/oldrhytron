<?php
/**
 * فایل انبار مواد اولیه
 * نمایش موجودی و تراکنش‌های انبار مواد اولیه
 */

function serial_production_material_inventory_page() {
    if (!current_user_can('manage_options')) {
        wp_die('<p>شما دسترسی به این بخش را ندارید.</p>');
    }

    global $wpdb;
    ob_start();

    $material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;
    ?>
    <div class="wrap">
        <h1>انبار مواد اولیه</h1>
        <form method="get">
            <input type="hidden" name="page" value="serial-material-inventory">
            <label>ماده اولیه:
                <select name="material_id" onchange="this.form.submit()">
                    <option value="">همه مواد اولیه</option>
                    <?php
                    $materials = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}materials");
                    foreach ($materials as $material) {
                        echo '<option value="' . $material->id . '" ' . selected($material_id, $material->id, false) . '>' . esc_html($material->name) . '</option>';
                    }
                    ?>
                </select>
            </label>
        </form>

        <h2>موجودی کلی</h2>
        <table class="report-container">
            <tr>
                <th>شناسه</th>
                <th>نام ماده اولیه</th>
                <th>واحد</th>
                <th>موجودی</th>
                <th>جزئیات</th>
            </tr>
            <?php
            $query = "SELECT m.id, m.name, u.name as unit_name, SUM(mi.quantity) as total_quantity 
                      FROM {$wpdb->prefix}materials m 
                      LEFT JOIN {$wpdb->prefix}material_inventory mi ON m.id = mi.material_id 
                      JOIN {$wpdb->prefix}units u ON m.unit_id = u.id";
            if ($material_id) {
                $query .= $wpdb->prepare(" WHERE m.id = %d", $material_id);
            }
            $query .= " GROUP BY m.id";
            $inventory = $wpdb->get_results($query);
            foreach ($inventory as $item) {
                echo '<tr>';
                echo '<td>' . esc_html($item->id) . '</td>';
                echo '<td>' . esc_html($item->name) . '</td>';
                echo '<td>' . esc_html($item->unit_name) . '</td>';
                echo '<td>' . number_format($item->total_quantity ?: 0) . '</td>';
                echo '<td><a href="?page=serial-material-inventory&material_id=' . $item->id . '">مشاهده جزئیات</a></td>';
                echo '</tr>';
            }
            ?>
        </table>

        <?php if ($material_id) { ?>
            <h2>جزئیات تراکنش‌ها برای ماده اولیه: <?php echo esc_html($wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}materials WHERE id = %d", $material_id))); ?></h2>
            <table class="report-container">
                <tr>
                    <th>شناسه</th>
                    <th>نوع تراکنش</th>
                    <th>سری ساخت</th>
                    <th>مقدار</th>
                    <th>قیمت (ریال)</th>
                    <th>کاربر</th>
                    <th>تاریخ</th>
                </tr>
                <?php
                $transactions = $wpdb->get_results($wpdb->prepare("SELECT mi.*, u.user_login, mp.price 
                    FROM {$wpdb->prefix}material_inventory mi 
                    LEFT JOIN {$wpdb->prefix}users u ON mi.user_id = u.id 
                    LEFT JOIN {$wpdb->prefix}material_prices mp ON mi.price_id = mp.id 
                    WHERE mi.material_id = %d ORDER BY mi.transaction_date DESC", $material_id));
                foreach ($transactions as $transaction) {
                    $type = $transaction->type == 'added' ? 'اضافه به انبار' : 'مصرف در تولید';
                    echo '<tr>';
                    echo '<td>' . esc_html($transaction->id) . '</td>';
                    echo '<td>' . $type . '</td>';
                    echo '<td>' . esc_html($transaction->serial_code ?: '-') . '</td>';
                    echo '<td>' . number_format($transaction->quantity) . '</td>';
                    echo '<td>' . number_format($transaction->price ?: 0) . '</td>';
                    echo '<td>' . esc_html($transaction->user_login ?: '-') . '</td>';
                    echo '<td>' . esc_html($transaction->transaction_date) . '</td>';
                    echo '</tr>';
                }
                ?>
            </table>
            <button onclick="window.print()">چاپ</button>
            <input type="checkbox" name="email_report" id="email_material_inventory" value="material_inventory"> <label for="email_material_inventory">ارسال گزارش به ایمیل</label>
            <button type="button" onclick="sendEmail('material_inventory', <?php echo $material_id; ?>)">ارسال به ایمیل</button>
        <?php } ?>
        <style>
            @media print {
                body * { visibility: hidden; }
                .report-container, .report-container * { visibility: visible; }
                .report-container { position: absolute; left: 0; top: 0; width: 100%; }
                button, input[type="checkbox"], label, form { display: none; }
            }
        </style>
        <script>
            function sendEmail(reportType, materialId) {
                if (document.getElementById('email_' + reportType).checked) {
                    const data = new FormData();
                    data.append('action', 'send_report_email');
                    data.append('report_type', reportType);
                    data.append('material_id', materialId);
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: data
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert('گزارش با موفقیت به ایمیل ارسال شد.');
                        } else {
                            alert('خطا در ارسال ایمیل: ' + result.data);
                        }
                    })
                    .catch(error => {
                        alert('خطا: ' + error);
                    });
                } else {
                    alert('لطفاً گزینه ارسال به ایمیل را انتخاب کنید.');
                }
            }
        </script>
    </div>
    <?php
    echo ob_get_clean();
}