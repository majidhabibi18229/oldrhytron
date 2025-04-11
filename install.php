<?php
/**
 * فایل نصب افزونه
 * ایجاد جداول مورد نیاز در دیتابیس
 */

// بارگذاری فایل upgrade.php برای دسترسی به تابع dbDelta
if (!function_exists('dbDelta')) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
}

function serial_production_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // جدول مواد اولیه
    $table_materials = $wpdb->prefix . 'materials';
    $sql_materials = "CREATE TABLE $table_materials (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        unit_id mediumint(9) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_materials);

    // جدول قیمت مواد اولیه
    $table_material_prices = $wpdb->prefix . 'material_prices';
    $sql_material_prices = "CREATE TABLE $table_material_prices (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        material_id mediumint(9) NOT NULL,
        price bigint(20) NOT NULL,
        purchase_date date NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_material_prices);

    // جدول ملزومات بسته‌بندی
    $table_packaging = $wpdb->prefix . 'packaging';
    $sql_packaging = "CREATE TABLE $table_packaging (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        unit_id mediumint(9) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_packaging);

    // جدول قیمت ملزومات بسته‌بندی
    $table_packaging_prices = $wpdb->prefix . 'packaging_prices';
    $sql_packaging_prices = "CREATE TABLE $table_packaging_prices (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        packaging_id mediumint(9) NOT NULL,
        price bigint(20) NOT NULL,
        purchase_date date NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_packaging_prices);

    // جدول محصولات
    $table_products = $wpdb->prefix . 'products';
    $sql_products = "CREATE TABLE $table_products (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        unit_id mediumint(9) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_products);

    // جدول کارگران
    $table_workers = $wpdb->prefix . 'workers';
    $sql_workers = "CREATE TABLE $table_workers (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        position varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_workers);

    // جدول ماشین‌آلات
    $table_machines = $wpdb->prefix . 'machines';
    $sql_machines = "CREATE TABLE $table_machines (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_machines);

    // جدول فرآیندها
    $table_processes = $wpdb->prefix . 'processes';
    $sql_processes = "CREATE TABLE $table_processes (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_processes);

    // جدول اقدامات عمومی
    $table_general_actions = $wpdb->prefix . 'general_actions';
    $sql_general_actions = "CREATE TABLE $table_general_actions (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_general_actions);

    // جدول کدهای سری ساخت
    $table_serial_codes = $wpdb->prefix . 'serial_codes';
    $sql_serial_codes = "CREATE TABLE $table_serial_codes (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        code varchar(10) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_serial_codes);

    // جدول سال‌های تولید
    $table_production_years = $wpdb->prefix . 'production_years';
    $sql_production_years = "CREATE TABLE $table_production_years (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        year int(4) NOT NULL,
        is_active tinyint(1) DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_production_years);

    // جدول واحدها
    $table_units = $wpdb->prefix . 'units';
    $sql_units = "CREATE TABLE $table_units (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_units);

    // جدول داده‌های تولید
    $table_production_data = $wpdb->prefix . 'production_data';
    $sql_production_data = "CREATE TABLE $table_production_data (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        serial_code varchar(50) NOT NULL,
        user_id bigint(20) NOT NULL,
        workers text,
        machines text,
        processes text,
        materials text,
        packaging text,
        products text,
        waste text,
        general_actions text,
        total_cost bigint(20) DEFAULT 0,
        production_date datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_production_data);

    // جدول ضایعات
    $table_waste = $wpdb->prefix . 'waste';
    $sql_waste = "CREATE TABLE $table_waste (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        serial_code varchar(50) NOT NULL,
        waste_details text,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_waste);

    // جدول دلایل
    $table_reasons = $wpdb->prefix . 'production_reasons';
    $sql_reasons = "CREATE TABLE $table_reasons (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        reason varchar(255) NOT NULL,
        type varchar(10) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_reasons);

    // جدول انبار محصول نهایی
    $table_final_product_inventory = $wpdb->prefix . 'final_product_inventory';
    $sql_final_product_inventory = "CREATE TABLE $table_final_product_inventory (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id mediumint(9) NOT NULL,
        quantity float NOT NULL,
        type varchar(20) NOT NULL,
        serial_code varchar(50),
        reason_id mediumint(9),
        user_id bigint(20) NOT NULL,
        transaction_date datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_final_product_inventory);

    // جدول انبار مواد اولیه
    $table_material_inventory = $wpdb->prefix . 'material_inventory';
    $sql_material_inventory = "CREATE TABLE $table_material_inventory (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        material_id mediumint(9) NOT NULL,
        quantity float NOT NULL,
        type varchar(20) NOT NULL,
        price_id mediumint(9),
        user_id bigint(20) NOT NULL,
        transaction_date datetime NOT NULL,
        serial_code varchar(50),
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_material_inventory);

    // جدول انبار ملزومات بسته‌بندی
    $table_packaging_inventory = $wpdb->prefix . 'packaging_inventory';
    $sql_packaging_inventory = "CREATE TABLE $table_packaging_inventory (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        packaging_id mediumint(9) NOT NULL,
        quantity float NOT NULL,
        type varchar(20) NOT NULL,
        price_id mediumint(9),
        user_id bigint(20) NOT NULL,
        transaction_date datetime NOT NULL,
        serial_code varchar(50),
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_packaging_inventory);

    // جدول ایمیل‌ها
    $table_email_destinations = $wpdb->prefix . 'email_destinations';
    $sql_email_destinations = "CREATE TABLE $table_email_destinations (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_email_destinations);
}