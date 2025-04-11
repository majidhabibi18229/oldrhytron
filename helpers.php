<?php
/**
 * فایل توابع کمکی
 * شامل توابع عمومی مورد نیاز افزونه
 */

function generate_serial_code($part, $year, $month, $day) {
    return sprintf("%s-%s%s%s", $part, $year, $month, $day);
}

function serial_code_exists($serial_code) {
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}production_data WHERE serial_code = %s", $serial_code));
    return $exists > 0;
}