<?php
/**
 * فایل انبار ملزومات بسته‌بندی
 * نمایش موجودی و تراکنش‌های انبار ملزومات بسته‌بندی
 */

function serial_production_packaging_inventory_page() {
    if (!current_user_can('manage_options')) {
        wp_die('<p>شما دسترسی به این بخش را ندارید.</p>');
    }

    global $wpdb;
    ob_start();

    $packaging_id = isset($_GET['packaging_id']) ? intval($_GET['packaging_id']) : 0;
    ?>
    <div class="wrap">
        <h1>انبار ملزومات بسته‌بندی</h1>
        <form method="get">
            <input type="hidden" name="page" value="serial-packaging-inventory">
            <label>ملزوم بسته‌بندی:
                <select name="packaging_id" onchange="this.form.submit()">
                    <option value="">همه ملزومات</option>
                    <?php
                    $packaging = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}packaging");
                    foreach ($packaging as $pack) {
                        echo '<option value="' . $pack->id . '" ' . selected($packaging_id, $pack->id, false) . '>' . esc_html($pack->name) . '</option>';
                    }
                    ?>
                </select>
            </label>
        </form>

        <h2>موجودی کلی</h2>
        <table class="report-container">
            <tr>
                <th>شناسه</th>
                <th>نام ملزوم</th>
                <th>واحد</th>
                <th>موجودی</th>
                <th>جزئیات</th>
            </tr>
            <?php
            $query = "SELECT p.id, p.name, u.name as unit_name, SUM(pi.quantity) as total_quantity 
                      FROM {$wpdb->prefix}packaging p 
                      LEFT JOIN {$wpdb->prefix}packaging_inventory pi ON p.id = pi.packaging_id 
                      JOIN {$wpdb->prefix}units u ON p.unit_id = u.id";
            if ($packaging_id) {
                $query .= $wpdb->prepare(" WHERE p.id = %d", $packaging_id);
            }
            $query .= " GROUP BY p.id";
            $inventory = $wpdb->get_results($query);
            foreach ($inventory as $item) {
                echo '<tr>';
                echo '<td>' . esc_html($item->id) . '</td>';
                echo '<td>' . esc_html($item->name) . '</td>';
                echo '<td>' . esc_html($item->unit_name) . '</td>';
                echo '<td>' . number_format($item->total_quantity ?: 0) . '</td>';
                echo '<td><a href="?page=serial-packaging-inventory&packaging_id=' . $item->id . '">مشاهده جزئیات</a></td>';
                echo '</tr>';
            }
            ?>
        </table>

        <?php if ($packaging_id) { ?>
            <h2>جزئیات تراکنش‌ها برای ملزوم بسته‌بندی: <?php echo esc_html($wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}packaging WHERE id = %d", $packaging_id))); ?></h2>
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
                $transactions = $wpdb->get_results($wpdb->prepare("SELECT pi.*, u.user_login, pp.price 
                    FROM {$wpdb->prefix}packaging_inventory pi 
                    LEFT JOIN {$wpdb->prefix}users u ON pi.user_id = u.id 
                    LEFT JOIN {$wpdb->prefix}packaging_prices pp ON pi.price_id = pp.id 
                    WHERE pi.packaging_id = %d ORDER BY pi.transaction_date DESC", $packaging_id));
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
            <input type="checkbox" name="email_report" id="email_packaging_inventory" value="packaging_inventory"> <label for="email_packaging_inventory">ارسال گزارش به ایمیل</label>
            <button type="button" onclick="sendEmail('packaging_inventory', <?php echo $packaging_id; ?>)">ارسال به ایمیل</button>
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
            function sendEmail(reportType, packagingId) {
                if (document.getElementById('email_' + reportType).checked) {
                    const data = new FormData();
                    data.append('action', 'send_report_email');
                    data.append('report_type', reportType);
                    data.append('packaging_id', packagingId);
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