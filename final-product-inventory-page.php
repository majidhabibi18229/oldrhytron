<?php
/**
 * فایل انبار محصول نهایی
 * نمایش موجودی و تراکنش‌های انبار محصول نهایی
 */

function serial_production_final_product_inventory_page() {
    if (!current_user_can('manage_options')) {
        wp_die('<p>شما دسترسی به این بخش را ندارید.</p>');
    }

    global $wpdb;
    ob_start();

    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    ?>
    <div class="wrap">
        <h1>انبار محصول نهایی</h1>
        <form method="get">
            <input type="hidden" name="page" value="serial-final-product-inventory">
            <label>محصول:
                <select name="product_id" onchange="this.form.submit()">
                    <option value="">همه محصولات</option>
                    <?php
                    $products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}products");
                    foreach ($products as $product) {
                        echo '<option value="' . $product->id . '" ' . selected($product_id, $product->id, false) . '>' . esc_html($product->name) . '</option>';
                    }
                    ?>
                </select>
            </label>
        </form>

        <h2>موجودی کلی</h2>
        <table class="report-container">
            <tr>
                <th>شناسه</th>
                <th>نام محصول</th>
                <th>واحد</th>
                <th>موجودی</th>
                <th>جزئیات</th>
            </tr>
            <?php
            $query = "SELECT p.id, p.name, u.name as unit_name, SUM(fpi.quantity) as total_quantity 
                      FROM {$wpdb->prefix}products p 
                      LEFT JOIN {$wpdb->prefix}final_product_inventory fpi ON p.id = fpi.product_id 
                      JOIN {$wpdb->prefix}units u ON p.unit_id = u.id";
            if ($product_id) {
                $query .= $wpdb->prepare(" WHERE p.id = %d", $product_id);
            }
            $query .= " GROUP BY p.id";
            $inventory = $wpdb->get_results($query);
            foreach ($inventory as $item) {
                echo '<tr>';
                echo '<td>' . esc_html($item->id) . '</td>';
                echo '<td>' . esc_html($item->name) . '</td>';
                echo '<td>' . esc_html($item->unit_name) . '</td>';
                echo '<td>' . number_format($item->total_quantity ?: 0) . '</td>';
                echo '<td><a href="?page=serial-final-product-inventory&product_id=' . $item->id . '">مشاهده جزئیات</a></td>';
                echo '</tr>';
            }
            ?>
        </table>

        <?php if ($product_id) { ?>
            <h2>جزئیات تراکنش‌ها برای محصول: <?php echo esc_html($wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}products WHERE id = %d", $product_id))); ?></h2>
            <table class="report-container">
                <tr>
                    <th>شناسه</th>
                    <th>نوع تراکنش</th>
                    <th>سری ساخت</th>
                    <th>مقدار</th>
                    <th>علت</th>
                    <th>کاربر</th>
                    <th>تاریخ</th>
                </tr>
                <?php
                $transactions = $wpdb->get_results($wpdb->prepare("SELECT fpi.*, u.user_login, r.reason 
                    FROM {$wpdb->prefix}final_product_inventory fpi 
                    LEFT JOIN {$wpdb->prefix}users u ON fpi.user_id = u.id 
                    LEFT JOIN {$wpdb->prefix}production_reasons r ON fpi.reason_id = r.id 
                    WHERE fpi.product_id = %d ORDER BY fpi.transaction_date DESC", $product_id));
                foreach ($transactions as $transaction) {
                    $type = $transaction->type == 'produced' ? 'تولید' : ($transaction->type == 'returned' ? 'برگشت به انبار' : 'خروج از انبار');
                    echo '<tr>';
                    echo '<td>' . esc_html($transaction->id) . '</td>';
                    echo '<td>' . $type . '</td>';
                    echo '<td>' . esc_html($transaction->serial_code ?: '-') . '</td>';
                    echo '<td>' . number_format($transaction->quantity) . '</td>';
                    echo '<td>' . esc_html($transaction->reason ?: '-') . '</td>';
                    echo '<td>' . esc_html($transaction->user_login ?: '-') . '</td>';
                    echo '<td>' . esc_html($transaction->transaction_date) . '</td>';
                    echo '</tr>';
                }
                ?>
            </table>
            <button onclick="window.print()">چاپ</button>
            <input type="checkbox" name="email_report" id="email_final_product_inventory" value="final_product_inventory"> <label for="email_final_product_inventory">ارسال گزارش به ایمیل</label>
            <button type="button" onclick="sendEmail('final_product_inventory', <?php echo $product_id; ?>)">ارسال به ایمیل</button>
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
            function sendEmail(reportType, productId) {
                if (document.getElementById('email_' + reportType).checked) {
                    const data = new FormData();
                    data.append('action', 'send_report_email');
                    data.append('report_type', reportType);
                    data.append('product_id', productId);
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