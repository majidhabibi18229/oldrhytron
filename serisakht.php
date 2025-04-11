<?php
/**
 * Plugin Name: مدیریت تولید سری‌ساخت
 * Description: افزونه‌ای برای مدیریت تولید، انبار، و گزارش‌گیری
 * Version: 1.0
 * Author: Your Name
 * Text Domain: serial-production
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تعریف مسیرها و ثابت‌ها
define('SERISAKHT_PATH', plugin_dir_path(__FILE__));
define('SERISAKHT_URL', plugin_dir_url(__FILE__));

// بارگذاری فایل‌های ماژولار
require_once SERISAKHT_PATH . 'includes/install.php';
require_once SERISAKHT_PATH . 'includes/helpers.php';
require_once SERISAKHT_PATH . 'includes/admin-page.php';
require_once SERISAKHT_PATH . 'includes/input-page.php';
require_once SERISAKHT_PATH . 'includes/report-page.php';
require_once SERISAKHT_PATH . 'includes/total-cost-page.php';
require_once SERISAKHT_PATH . 'includes/reasons-page.php';
require_once SERISAKHT_PATH . 'includes/waste-page.php';
require_once SERISAKHT_PATH . 'includes/final-product-inventory-page.php';
require_once SERISAKHT_PATH . 'includes/material-inventory-page.php';
require_once SERISAKHT_PATH . 'includes/packaging-inventory-page.php';

// فعال‌سازی افزونه
register_activation_hook(__FILE__, 'serial_production_install');

// بارگذاری استایل‌ها و اسکریپت‌ها
add_action('admin_enqueue_scripts', 'serial_production_enqueue_assets');
function serial_production_enqueue_assets($hook) {
    if (strpos($hook, 'serial-production') !== false) {
        wp_enqueue_style('serial-production-style', SERISAKHT_URL . 'assets/css/style.css');
        wp_enqueue_script('serial-production-script', SERISAKHT_URL . 'assets/js/script.js', ['jquery'], '1.0', true);
    }
}

// ثبت منوها
add_action('admin_menu', 'serial_production_menu');
function serial_production_menu() {
    add_menu_page(
        'مدیریت تولید',
        'مدیریت تولید',
        'manage_options',
        'serial-production',
        'serial_production_admin_page',
        'dashicons-factory',
        6
    );

    add_submenu_page(
        'serial-production',
        'ورود اطلاعات',
        'ورود اطلاعات',
        'manage_options',
        'serial-production-input',
        'serial_production_input_page'
    );

    add_submenu_page(
        'serial-production',
        'گزارش‌گیری',
        'گزارش‌گیری',
        'manage_options',
        'serial-production-report',
        'serial_production_report_page'
    );

    add_submenu_page(
        'serial-production',
        'قیمت تمام‌شده',
        'قیمت تمام‌شده',
        'manage_options',
        'serial-total-cost',
        'serial_total_cost_page'
    );

    add_submenu_page(
        'serial-production',
        'مدیریت دلایل',
        'مدیریت دلایل',
        'manage_options',
        'serial-production-reasons',
        'serial_production_reasons_page'
    );

    add_submenu_page(
        'serial-production',
        'مدیریت ضایعات',
        'مدیریت ضایعات',
        'manage_options',
        'serial-production-waste',
        'serial_production_waste_page'
    );

    add_submenu_page(
        'serial-production',
        'انبار محصول نهایی',
        'انبار محصول نهایی',
        'manage_options',
        'serial-final-product-inventory',
        'serial_production_final_product_inventory_page'
    );

    add_submenu_page(
        'serial-production',
        'انبار مواد اولیه',
        'انبار مواد اولیه',
        'manage_options',
        'serial-material-inventory',
        'serial_production_material_inventory_page'
    );

    add_submenu_page(
        'serial-production',
        'انبار ملزومات بسته‌بندی',
        'انبار ملزومات بسته‌بندی',
        'manage_options',
        'serial-packaging-inventory',
        'serial_production_packaging_inventory_page'
    );
}

// اکشن برای ارسال ایمیل
add_action('wp_ajax_send_report_email', 'serial_production_send_report_email');
function serial_production_send_report_email() {
    global $wpdb;
    $report_type = sanitize_text_field($_POST['report_type']);
    $serial_code = isset($_POST['serial_code']) ? sanitize_text_field($_POST['serial_code']) : '';
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $material_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : 0;
    $packaging_id = isset($_POST['packaging_id']) ? intval($_POST['packaging_id']) : 0;

    $emails = $wpdb->get_results("SELECT email FROM {$wpdb->prefix}email_destinations");
    if (!$emails) {
        wp_send_json_error('هیچ ایمیل مقصدی تعریف نشده است.');
    }

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $subject = '';
    $body = '';

    if ($report_type == 'production_report') {
        $subject = "گزارش تولید - سری ساخت $serial_code";
        $production_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}production_data WHERE serial_code = %s", $serial_code));
        if (!$production_data) {
            wp_send_json_error('سری ساخت یافت نشد.');
        }

        $body = '<h2>گزارش سری ساخت ' . esc_html($serial_code) . '</h2>';
        $body .= '<table border="1" style="border-collapse: collapse; width: 100%;">';
        $body .= '<tr><th>تاریخ تولید</th><td>' . esc_html($production_data->production_date) . '</td></tr>';

        $workers = json_decode($production_data->workers, true);
        $worker_names = [];
        if (is_array($workers)) {
            foreach ($workers as $worker_id) {
                $worker = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}workers WHERE id = %d", $worker_id));
                if ($worker) {
                    $worker_names[] = $worker->name;
                }
            }
        }
        $body .= '<tr><th>نیروی انسانی</th><td>' . (implode(', ', array_map('esc_html', $worker_names)) ?: 'هیچ کارگری ثبت نشده است.') . '</td></tr>';

        $machines = json_decode($production_data->machines, true);
        $machine_names = [];
        if (is_array($machines)) {
            foreach ($machines as $machine_id) {
                $machine = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}machines WHERE id = %d", $machine_id));
                if ($machine) {
                    $machine_names[] = $machine->name;
                }
            }
        }
        $body .= '<tr><th>ماشین‌آلات</th><td>' . (implode(', ', array_map('esc_html', $machine_names)) ?: 'هیچ ماشین‌آلاتی ثبت نشده است.') . '</td></tr>';

        $processes = json_decode($production_data->processes, true);
        $process_names = [];
        if (is_array($processes)) {
            foreach ($processes as $process_id) {
                $process = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}processes WHERE id = %d", $process_id));
                if ($process) {
                    $process_names[] = $process->name;
                }
            }
        }
        $body .= '<tr><th>فرآیندها</th><td>' . (implode(', ', array_map('esc_html', $process_names)) ?: 'هیچ فرآیندی ثبت نشده است.') . '</td></tr>';

        $materials = json_decode($production_data->materials, true);
        $material_text = '';
        if (is_array($materials)) {
            foreach ($materials as $material) {
                $material_data = $wpdb->get_row($wpdb->prepare("SELECT name, unit_id FROM {$wpdb->prefix}materials WHERE id = %d", $material['id']));
                if ($material_data) {
                    $unit = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}units WHERE id = %d", $material_data->unit_id));
                    $price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}material_prices WHERE id = %d", $material['price_id']));
                    $material_text .= esc_html($material_data->name) . ": " . number_format($material['quantity']) . " (" . esc_html($unit) . ") به ارزش " . number_format($price * $material['quantity']) . " ریال<br>";
                }
            }
        }
        $body .= '<tr><th>مواد اولیه مصرفی</th><td>' . ($material_text ?: 'هیچ ماده اولیه‌ای ثبت نشده است.') . '</td></tr>';

        $packaging = json_decode($production_data->packaging, true);
        $packaging_text = '';
        if (is_array($packaging)) {
            foreach ($packaging as $pack) {
                $pack_data = $wpdb->get_row($wpdb->prepare("SELECT name, unit_id FROM {$wpdb->prefix}packaging WHERE id = %d", $pack['id']));
                if ($pack_data) {
                    $unit = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}units WHERE id = %d", $pack_data->unit_id));
                    $price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}packaging_prices WHERE id = %d", $pack['price_id']));
                    $packaging_text .= esc_html($pack_data->name) . ": " . number_format($pack['quantity']) . " (" . esc_html($unit) . ") به ارزش " . number_format($price * $pack['quantity']) . " ریال<br>";
                }
            }
        }
        $body .= '<tr><th>ملزومات بسته‌بندی</th><td>' . ($packaging_text ?: 'هیچ ملزوم بسته‌بندی ثبت نشده است.') . '</td></tr>';

        $products = json_decode($production_data->products, true);
        $product_text = '';
        if (is_array($products)) {
            foreach ($products as $product) {
                $product_data = $wpdb->get_row($wpdb->prepare("SELECT name, unit_id FROM {$wpdb->prefix}products WHERE id = %d", $product['id']));
                if ($product_data) {
                    $unit = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}units WHERE id = %d", $product_data->unit_id));
                    $product_text .= esc_html($product_data->name) . ": " . number_format($product['quantity']) . " (" . esc_html($unit) . ")<br>";
                }
            }
        }
        $body .= '<tr><th>محصولات تولیدی</th><td>' . ($product_text ?: 'هیچ محصولی ثبت نشده است.') . '</td></tr>';

        $waste = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}waste WHERE serial_code = %s", $serial_code));
        $waste_text = '';
        if ($waste) {
            $details = json_decode($waste->waste_details, true);
            if (is_array($details) && isset($details['materials'])) {
                foreach ($details['materials'] as $index => $material_id) {
                    $material = $wpdb->get_row($wpdb->prepare("SELECT name, unit_id FROM {$wpdb->prefix}materials WHERE id = %d", $material_id));
                    if ($material) {
                        $unit = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}units WHERE id = %d", $material->unit_id));
                        $price_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}material_prices WHERE material_id = %d AND purchase_date <= %s ORDER BY purchase_date DESC LIMIT 1", $material_id, $production_data->production_date));
                        $price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}material_prices WHERE id = %d", $price_id));
                        $waste_text .= esc_html($material->name) . ": " . number_format($details['quantities'][$index]) . " (" . esc_html($unit) . ") به ارزش " . number_format($price * $details['quantities'][$index]) . " ریال<br>";
                    }
                }
            }
            if (is_array($details) && isset($details['packaging'])) {
                foreach ($details['packaging'] as $index => $pack_id) {
                    $pack = $wpdb->get_row($wpdb->prepare("SELECT name, unit_id FROM {$wpdb->prefix}packaging WHERE id = %d", $pack_id));
                    if ($pack) {
                        $unit = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}units WHERE id = %d", $pack->unit_id));
                        $price_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}packaging_prices WHERE packaging_id = %d AND purchase_date <= %s ORDER BY purchase_date DESC LIMIT 1", $pack_id, $production_data->production_date));
                        $price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}packaging_prices WHERE id = %d", $price_id));
                        $waste_text .= esc_html($pack->name) . ": " . number_format($details['packaging_quantities'][$index]) . " (" . esc_html($unit) . ") به ارزش " . number_format($price * $details['packaging_quantities'][$index]) . " ریال<br>";
                    }
                }
            }
        }
        $body .= '<tr><th>ضایعات</th><td>' . ($waste_text ?: 'هیچ ضایعاتی ثبت نشده است.') . '</td></tr>';

        $general_actions = json_decode($production_data->general_actions, true);
        $action_names = [];
        if (is_array($general_actions)) {
            foreach ($general_actions as $action_id) {
                $action = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}general_actions WHERE id = %d", $action_id));
                if ($action) {
                    $action_names[] = $action->name;
                }
            }
        }
        $body .= '<tr><th>اقدامات عمومی</th><td>' . (implode(', ', array_map('esc_html', $action_names)) ?: 'هیچ اقدامی ثبت نشده است.') . '</td></tr>';

        $body .= '<tr><th>قیمت تمام‌شده</th><td>' . number_format($production_data->total_cost) . ' ریال</td></tr>';
        $body .= '</table>';

    } elseif ($report_type == 'total_cost_report') {
        $subject = "گزارش قیمت تمام‌شده - سری ساخت $serial_code";
        $production_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}production_data WHERE serial_code = %s", $serial_code));
        if (!$production_data) {
            wp_send_json_error('سری ساخت یافت نشد.');
        }

        $total_cost = $production_data->total_cost;
        $products = json_decode($production_data->products, true);
        $total_quantity = 0;
        if (is_array($products)) {
            foreach ($products as $product) {
                $total_quantity += $product['quantity'];
            }
        }
        $cost_per_unit = $total_quantity > 0 ? $total_cost / $total_quantity : 0;

        $body = '<h2>قیمت تمام‌شده سری ساخت ' . esc_html($serial_code) . '</h2>';
        $body .= '<table border="1" style="border-collapse: collapse; width: 100%;">';
        $body .= '<tr><th>تاریخ تولید</th><td>' . esc_html($production_data->production_date) . '</td></tr>';

        $materials = json_decode($production_data->materials, true);
        $material_text = '';
        if (is_array($materials)) {
            foreach ($materials as $material) {
                $material_data = $wpdb->get_row($wpdb->prepare("SELECT name, unit_id FROM {$wpdb->prefix}materials WHERE id = %d", $material['id']));
                if ($material_data) {
                    $unit = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}units WHERE id = %d", $material_data->unit_id));
                    $price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}material_prices WHERE id = %d", $material['price_id']));
                    $material_text .= esc_html($material_data->name) . ": " . number_format($material['quantity']) . " (" . esc_html($unit) . ") به ارزش " . number_format($price * $material['quantity']) . " ریال<br>";
                }
            }
        }
        $body .= '<tr><th>مواد اولیه مصرفی</th><td>' . ($material_text ?: 'هیچ ماده اولیه‌ای ثبت نشده است.') . '</td></tr>';

        $packaging = json_decode($production_data->packaging, true);
        $packaging_text = '';
        if (is_array($packaging)) {
            foreach ($packaging as $pack) {
                $pack_data = $wpdb->get_row($wpdb->prepare("SELECT name, unit_id FROM {$wpdb->prefix}packaging WHERE id = %d", $pack['id']));
                if ($pack_data) {
                    $unit = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}units WHERE id = %d", $pack_data->unit_id));
                    $price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}packaging_prices WHERE id = %d", $pack['price_id']));
                    $packaging_text .= esc_html($pack_data->name) . ": " . number_format($pack['quantity']) . " (" . esc_html($unit) . ") به ارزش " . number_format($price * $pack['quantity']) . " ریال<br>";
                }
            }
        }
        $body .= '<tr><th>ملزومات بسته‌بندی</th><td>' . ($packaging_text ?: 'هیچ ملزوم بسته‌بندی ثبت نشده است.') . '</td></tr>';

        $products = json_decode($production_data->products, true);
        $product_text = '';
        if (is_array($products)) {
            foreach ($products as $product) {
                $product_data = $wpdb->get_row($wpdb->prepare("SELECT name, unit_id FROM {$wpdb->prefix}products WHERE id = %d", $product['id']));
                if ($product_data) {
                    $unit = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}units WHERE id = %d", $product_data->unit_id));
                    $product_text .= esc_html($product_data->name) . ": " . number_format($product['quantity']) . " (" . esc_html($unit) . ")<br>";
                }
            }
        }
        $body .= '<tr><th>محصولات تولیدی</th><td>' . ($product_text ?: 'هیچ محصولی ثبت نشده است.') . '</td></tr>';

        $body .= '<tr><th>هزینه کل</th><td>' . number_format($total_cost) . ' ریال</td></tr>';
        $body .= '<tr><th>هزینه تولید هر واحد</th><td>' . number_format($cost_per_unit) . ' ریال</td></tr>';
        $body .= '</table>';

    } elseif ($report_type == 'final_product_inventory') {
        $subject = "گزارش انبار محصول نهایی - محصول $product_id";
        $product_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}products WHERE id = %d", $product_id));
        $body = '<h2>گزارش انبار محصول نهایی برای محصول: ' . esc_html($product_name) . '</h2>';

        $total_quantity = $wpdb->get_var($wpdb->prepare("SELECT SUM(quantity) FROM {$wpdb->prefix}final_product_inventory WHERE product_id = %d", $product_id));
        $body .= '<p>موجودی کلی: ' . number_format($total_quantity ?: 0) . '</p>';

        $transactions = $wpdb->get_results($wpdb->prepare("SELECT fpi.*, u.user_login, r.reason 
            FROM {$wpdb->prefix}final_product_inventory fpi 
            LEFT JOIN {$wpdb->prefix}users u ON fpi.user_id = u.id 
            LEFT JOIN {$wpdb->prefix}production_reasons r ON fpi.reason_id = r.id 
            WHERE fpi.product_id = %d ORDER BY fpi.transaction_date DESC", $product_id));
        $body .= '<table border="1" style="border-collapse: collapse; width: 100%;">';
        $body .= '<tr><th>شناسه</th><th>نوع تراکنش</th><th>سری ساخت</th><th>مقدار</th><th>علت</th><th>کاربر</th><th>تاریخ</th></tr>';
        foreach ($transactions as $transaction) {
            $type = $transaction->type == 'produced' ? 'تولید' : ($transaction->type == 'returned' ? 'برگشت به انبار' : 'خروج از انبار');
            $body .= '<tr>';
            $body .= '<td>' . esc_html($transaction->id) . '</td>';
            $body .= '<td>' . $type . '</td>';
            $body .= '<td>' . esc_html($transaction->serial_code ?: '-') . '</td>';
            $body .= '<td>' . number_format($transaction->quantity) . '</td>';
            $body .= '<td>' . esc_html($transaction->reason ?: '-') . '</td>';
            $body .= '<td>' . esc_html($transaction->user_login ?: '-') . '</td>';
            $body .= '<td>' . esc_html($transaction->transaction_date) . '</td>';
            $body .= '</tr>';
        }
        $body .= '</table>';

    } elseif ($report_type == 'material_inventory') {
        $subject = "گزارش انبار مواد اولیه - ماده اولیه $material_id";
        $material_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}materials WHERE id = %d", $material_id));
        $body = '<h2>گزارش انبار مواد اولیه برای ماده اولیه: ' . esc_html($material_name) . '</h2>';

        $total_quantity = $wpdb->get_var($wpdb->prepare("SELECT SUM(quantity) FROM {$wpdb->prefix}material_inventory WHERE material_id = %d", $material_id));
        $body .= '<p>موجودی کلی: ' . number_format($total_quantity ?: 0) . '</p>';

        $transactions = $wpdb->get_results($wpdb->prepare("SELECT mi.*, u.user_login, mp.price 
            FROM {$wpdb->prefix}material_inventory mi 
            LEFT JOIN {$wpdb->prefix}users u ON mi.user_id = u.id 
            LEFT JOIN {$wpdb->prefix}material_prices mp ON mi.price_id = mp.id 
            WHERE mi.material_id = %d ORDER BY mi.transaction_date DESC", $material_id));
        $body .= '<table border="1" style="border-collapse: collapse; width: 100%;">';
        $body .= '<tr><th>شناسه</th><th>نوع تراکنش</th><th>سری ساخت</th><th>مقدار</th><th>قیمت (ریال)</th><th>کاربر</th><th>تاریخ</th></tr>';
        foreach ($transactions as $transaction) {
            $type = $transaction->type == 'added' ? 'اضافه به انبار' : 'مصرف در تولید';
            $body .= '<tr>';
            $body .= '<td>' . esc_html($transaction->id) . '</td>';
            $body .= '<td>' . $type . '</td>';
            $body .= '<td>' . esc_html($transaction->serial_code ?: '-') . '</td>';
            $body .= '<td>' . number_format($transaction->quantity) . '</td>';
            $body .= '<td>' . number_format($transaction->price ?: 0) . '</td>';
            $body .= '<td>' . esc_html($transaction->user_login ?: '-') . '</td>';
            $body .= '<td>' . esc_html($transaction->transaction_date) . '</td>';
            $body .= '</tr>';
        }
        $body .= '</table>';

    } elseif ($report_type == 'packaging_inventory') {
        $subject = "گزارش انبار ملزومات بسته‌بندی - ملزوم $packaging_id";
        $packaging_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}packaging WHERE id = %d", $packaging_id));
        $body = '<h2>گزارش انبار ملزومات بسته‌بندی برای ملزوم: ' . esc_html($packaging_name) . '</h2>';

        $total_quantity = $wpdb->get_var($wpdb->prepare("SELECT SUM(quantity) FROM {$wpdb->prefix}packaging_inventory WHERE packaging_id = %d", $packaging_id));
        $body .= '<p>موجودی کلی: ' . number_format($total_quantity ?: 0) . '</p>';

        $transactions = $wpdb->get_results($wpdb->prepare("SELECT pi.*, u.user_login, pp.price 
            FROM {$wpdb->prefix}packaging_inventory pi 
            LEFT JOIN {$wpdb->prefix}users u ON pi.user_id = u.id 
            LEFT JOIN {$wpdb->prefix}packaging_prices pp ON pi.price_id = pp.id 
            WHERE pi.packaging_id = %d ORDER BY pi.transaction_date DESC", $packaging_id));
        $body .= '<table border="1" style="border-collapse: collapse; width: 100%;">';
        $body .= '<tr><th>شناسه</th><th>نوع تراکنش</th><th>سری ساخت</th><th>مقدار</th><th>قیمت (ریال)</th><th>کاربر</th><th>تاریخ</th></tr>';
        foreach ($transactions as $transaction) {
            $type = $transaction->type == 'added' ? 'اضافه به انبار' : 'مصرف در تولید';
            $body .= '<tr>';
            $body .= '<td>' . esc_html($transaction->id) . '</td>';
            $body .= '<td>' . $type . '</td>';
            $body .= '<td>' . esc_html($transaction->serial_code ?: '-') . '</td>';
            $body .= '<td>' . number_format($transaction->quantity) . '</td>';
            $body .= '<td>' . number_format($transaction->price ?: 0) . '</td>';
            $body .= '<td>' . esc_html($transaction->user_login ?: '-') . '</td>';
            $body .= '<td>' . esc_html($transaction->transaction_date) . '</td>';
            $body .= '</tr>';
        }
        $body .= '</table>';
    }

    $success = true;
    foreach ($emails as $email) {
        if (!wp_mail($email->email, $subject, $body, $headers)) {
            $success = false;
        }
    }

    if ($success) {
        wp_send_json_success('ایمیل با موفقیت ارسال شد.');
    } else {
        wp_send_json_error('خطا در ارسال ایمیل.');
    }
}