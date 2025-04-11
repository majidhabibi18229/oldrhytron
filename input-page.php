<?php
/**
 * فایل ورود اطلاعات تولید
 * فرم ورود اطلاعات تولید و ثبت در دیتابیس
 */

function serial_production_input_page() {
    if (!current_user_can('manage_options')) {
        wp_die('<p>شما دسترسی به این بخش را ندارید.</p>');
    }

    global $wpdb;
    ob_start();

    $message = '';
    if (isset($_POST['submit_production'])) {
        $serial_code_part = sanitize_text_field($_POST['serial_code_part']);
        $year = sanitize_text_field($_POST['year']);
        $month = sanitize_text_field($_POST['month']);
        $day = sanitize_text_field($_POST['day']);
        $serial_code = generate_serial_code($serial_code_part, $year, $month, $day);

        if (serial_code_exists($serial_code)) {
            $message = '<p style="color: red;">سری ساخت قبلاً ثبت شده است.</p>';
        } else {
            $workers = isset($_POST['workers']) ? json_decode(stripslashes($_POST['workers']), true) : [];
            $machines = isset($_POST['machines']) ? json_decode(stripslashes($_POST['machines']), true) : [];
            $processes = isset($_POST['processes']) ? json_decode(stripslashes($_POST['processes']), true) : [];
            $materials = isset($_POST['materials']) ? json_decode(stripslashes($_POST['materials']), true) : [];
            $packaging = isset($_POST['packaging']) ? json_decode(stripslashes($_POST['packaging']), true) : [];
            $products = isset($_POST['products']) ? json_decode(stripslashes($_POST['products']), true) : [];
            $general_actions = isset($_POST['general_actions']) ? json_decode(stripslashes($_POST['general_actions']), true) : [];

            $total_cost = 0;
            $error = false;

            // لاگ برای دیباگ
            error_log("Materials: " . print_r($materials, true));
            error_log("Packaging: " . print_r($packaging, true));
            error_log("Products: " . print_r($products, true));

            // بررسی اجباری بودن مواد اولیه و ملزومات بسته‌بندی
            if (empty($materials)) {
                $message = '<p style="color: red;">لطفاً حداقل یک ماده اولیه انتخاب کنید.</p>';
                $error = true;
            }
            if (empty($packaging)) {
                $message = '<p style="color: red;">لطفاً حداقل یک ملزوم بسته‌بندی انتخاب کنید.</p>';
                $error = true;
            }

            // بررسی موجودی مواد اولیه و کسر از انبار
            if (!$error && is_array($materials) && !empty($materials)) {
                foreach ($materials as $material) {
                    $material_id = intval($material['id']);
                    $quantity_needed = floatval($material['quantity']);
                    $waste_quantity = !empty($material['waste']) ? floatval($material['waste']) : 0;
                    $total_quantity_needed = $quantity_needed + $waste_quantity;

                    // محاسبه موجودی فعلی
                    $total_material_quantity = 0;
                    $transactions = $wpdb->get_results($wpdb->prepare(
                        "SELECT quantity FROM {$wpdb->prefix}material_inventory WHERE material_id = %d",
                        $material_id
                    ));
                    foreach ($transactions as $transaction) {
                        $total_material_quantity += floatval($transaction->quantity);
                    }

                    error_log("Material ID: $material_id, Total Quantity: $total_material_quantity, Needed: $total_quantity_needed");

                    if ($total_material_quantity < $total_quantity_needed) {
                        $material_name = $wpdb->get_var($wpdb->prepare(
                            "SELECT name FROM {$wpdb->prefix}materials WHERE id = %d",
                            $material_id
                        ));
                        $message = '<p style="color: red;">موجودی ماده اولیه ' . esc_html($material_name) . ' کافی نیست. موجودی فعلی: ' . number_format($total_material_quantity) . '</p>';
                        $error = true;
                        break;
                    }

                    $price = $wpdb->get_var($wpdb->prepare(
                        "SELECT price FROM {$wpdb->prefix}material_prices WHERE id = %d",
                        $material['price_id']
                    ));
                    $total_cost += $price * $quantity_needed;

                    // کسر از انبار
                    $result = $wpdb->insert($wpdb->prefix . 'material_inventory', [
                        'material_id' => $material_id,
                        'quantity' => -$total_quantity_needed,
                        'type' => 'used',
                        'price_id' => $material['price_id'],
                        'user_id' => get_current_user_id(),
                        'transaction_date' => current_time('mysql'),
                        'serial_code' => $serial_code
                    ]);
                    if ($result === false) {
                        error_log("Failed to insert material inventory: " . $wpdb->last_error);
                        $message = '<p style="color: red;">خطا در کسر از موجودی مواد اولیه.</p>';
                        $error = true;
                        break;
                    }
                }
            }

            // بررسی موجودی ملزومات بسته‌بندی و کسر از انبار
            if (!$error && is_array($packaging) && !empty($packaging)) {
                foreach ($packaging as $pack) {
                    $packaging_id = intval($pack['id']);
                    $quantity_needed = floatval($pack['quantity']);
                    $waste_quantity = !empty($pack['waste']) ? floatval($pack['waste']) : 0;
                    $total_quantity_needed = $quantity_needed + $waste_quantity;

                    // محاسبه موجودی فعلی
                    $total_packaging_quantity = 0;
                    $transactions = $wpdb->get_results($wpdb->prepare(
                        "SELECT quantity FROM {$wpdb->prefix}packaging_inventory WHERE packaging_id = %d",
                        $packaging_id
                    ));
                    foreach ($transactions as $transaction) {
                        $total_packaging_quantity += floatval($transaction->quantity);
                    }

                    error_log("Packaging ID: $packaging_id, Total Quantity: $total_packaging_quantity, Needed: $total_quantity_needed");

                    if ($total_packaging_quantity < $total_quantity_needed) {
                        $packaging_name = $wpdb->get_var($wpdb->prepare(
                            "SELECT name FROM {$wpdb->prefix}packaging WHERE id = %d",
                            $packaging_id
                        ));
                        $message = '<p style="color: red;">موجودی ملزوم بسته‌بندی ' . esc_html($packaging_name) . ' کافی نیست. موجودی فعلی: ' . number_format($total_packaging_quantity) . '</p>';
                        $error = true;
                        break;
                    }

                    $price = $wpdb->get_var($wpdb->prepare(
                        "SELECT price FROM {$wpdb->prefix}packaging_prices WHERE id = %d",
                        $pack['price_id']
                    ));
                    $total_cost += $price * $quantity_needed;

                    // کسر از انبار
                    $result = $wpdb->insert($wpdb->prefix . 'packaging_inventory', [
                        'packaging_id' => $packaging_id,
                        'quantity' => -$total_quantity_needed,
                        'type' => 'used',
                        'price_id' => $pack['price_id'],
                        'user_id' => get_current_user_id(),
                        'transaction_date' => current_time('mysql'),
                        'serial_code' => $serial_code
                    ]);
                    if ($result === false) {
                        error_log("Failed to insert packaging inventory: " . $wpdb->last_error);
                        $message = '<p style="color: red;">خطا در کسر از موجودی ملزومات بسته‌بندی.</p>';
                        $error = true;
                        break;
                    }
                }
            }

            // اگر خطایی وجود نداشت، ادامه ثبت
            if (!$error) {
                // ثبت محصولات تولیدی در انبار محصول نهایی
                if (is_array($products) && !empty($products)) {
                    foreach ($products as $product) {
                        $product_id = intval($product['id']);
                        $quantity = floatval($product['quantity']);
                        error_log("Inserting product: ID=$product_id, Quantity=$quantity, Serial Code=$serial_code");
                        $result = $wpdb->insert($wpdb->prefix . 'final_product_inventory', [
                            'product_id' => $product_id,
                            'quantity' => $quantity,
                            'type' => 'produced',
                            'serial_code' => $serial_code,
                            'user_id' => get_current_user_id(),
                            'transaction_date' => current_time('mysql')
                        ]);
                        if ($result === false) {
                            error_log("Failed to insert final product inventory: " . $wpdb->last_error);
                            $message = '<p style="color: red;">خطا در ثبت محصول تولیدی: ' . esc_html($wpdb->last_error) . '</p>';
                            $error = true;
                            break;
                        }
                    }
                } else {
                    $message = '<p style="color: red;">هیچ محصولی برای ثبت انتخاب نشده است.</p>';
                    $error = true;
                }

                // اگر خطایی وجود نداشت، ادامه ثبت
                if (!$error) {
                    // ثبت ضایعات
                    $waste_data = [
                        'materials' => [],
                        'quantities' => [],
                        'packaging' => [],
                        'packaging_quantities' => []
                    ];
                    if (is_array($materials)) {
                        foreach ($materials as $material) {
                            if (!empty($material['waste'])) {
                                $waste_data['materials'][] = $material['id'];
                                $waste_data['quantities'][] = $material['waste'];
                            }
                        }
                    }
                    if (is_array($packaging)) {
                        foreach ($packaging as $pack) {
                            if (!empty($pack['waste'])) {
                                $waste_data['packaging'][] = $pack['id'];
                                $waste_data['packaging_quantities'][] = $pack['waste'];
                            }
                        }
                    }
                    if (!empty($waste_data['materials']) || !empty($waste_data['packaging'])) {
                        $result = $wpdb->insert($wpdb->prefix . 'waste', [
                            'serial_code' => $serial_code,
                            'waste_details' => json_encode($waste_data, JSON_UNESCAPED_UNICODE),
                            'created_at' => current_time('mysql')
                        ]);
                        if ($result === false) {
                            error_log("Failed to insert waste: " . $wpdb->last_error);
                        }
                    }

                    // ثبت اطلاعات تولید
                    $result = $wpdb->insert($wpdb->prefix . 'production_data', [
                        'serial_code' => $serial_code,
                        'user_id' => get_current_user_id(),
                        'workers' => json_encode($workers, JSON_UNESCAPED_UNICODE),
                        'machines' => json_encode($machines, JSON_UNESCAPED_UNICODE),
                        'processes' => json_encode($processes, JSON_UNESCAPED_UNICODE),
                        'materials' => json_encode($materials, JSON_UNESCAPED_UNICODE),
                        'packaging' => json_encode($packaging, JSON_UNESCAPED_UNICODE),
                        'products' => json_encode($products, JSON_UNESCAPED_UNICODE),
                        'waste' => json_encode($waste_data, JSON_UNESCAPED_UNICODE),
                        'general_actions' => json_encode($general_actions, JSON_UNESCAPED_UNICODE),
                        'total_cost' => $total_cost,
                        'production_date' => current_time('mysql')
                    ]);

                    if ($result === false) {
                        error_log("Failed to insert production data: " . $wpdb->last_error);
                        $message = '<p style="color: red;">خطا در ثبت اطلاعات تولید.</p>';
                    } else {
                        $message = '<p style="color: green;">تولید با موفقیت ثبت شد.</p>';
                    }
                }
            }
        }
    }
    ?>
    <div class="wrap">
        <h1>ورود اطلاعات تولید</h1>
        <?php if ($message) echo $message; ?>
        <form method="post" class="serial-production-form">
            <h3>سری ساخت</h3>
            <label>بخش سری ساخت:
                <select name="serial_code_part" id="serial_code_part" onchange="updateSerialCode()" required>
                    <option value="">انتخاب کنید</option>
                    <?php
                    $serial_codes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}serial_codes");
                    foreach ($serial_codes as $code) {
                        echo '<option value="' . esc_attr($code->code) . '">' . esc_html($code->name) . '</option>';
                    }
                    ?>
                </select>
            </label>
            <label>سال:
                <select name="year" id="year" onchange="updateSerialCode()" required>
                    <option value="">انتخاب کنید</option>
                    <?php
                    $years = $wpdb->get_results("SELECT year FROM {$wpdb->prefix}production_years");
                    foreach ($years as $year) {
                        echo '<option value="' . esc_attr($year->year) . '">' . esc_html($year->year) . '</option>';
                    }
                    ?>
                </select>
            </label>
            <label>ماه:
                <select name="month" id="month" onchange="updateSerialCode()" required>
                    <option value="">انتخاب کنید</option>
                    <?php for ($i = 1; $i <= 12; $i++) {
                        $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                        echo '<option value="' . $month . '">' . $month . '</option>';
                    } ?>
                </select>
            </label>
            <label>روز:
                <select name="day" id="day" onchange="updateSerialCode()" required>
                    <option value="">انتخاب کنید</option>
                    <?php for ($i = 1; $i <= 31; $i++) {
                        $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                        echo '<option value="' . $day . '">' . $day . '</option>';
                    } ?>
                </select>
            </label>
            <p>سری ساخت تولید: <span id="generated_serial_code"></span></p>

            <h3>نیروی انسانی</h3>
            <label>کارگر:
                <select id="worker_select">
                    <option value="">انتخاب کنید</option>
                    <?php
                    $workers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}workers");
                    foreach ($workers as $worker) {
                        echo '<option value="' . $worker->id . '">' . esc_html($worker->name) . '</option>';
                    }
                    ?>
                </select>
                <button type="button" onclick="addItem('worker')">افزودن</button>
            </label>
            <ul id="worker_list"></ul>
            <input type="hidden" name="workers" id="workers_input">

            <h3>ماشین‌آلات</h3>
            <label>ماشین:
                <select id="machine_select">
                    <option value="">انتخاب کنید</option>
                    <?php
                    $machines = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}machines");
                    foreach ($machines as $machine) {
                        echo '<option value="' . $machine->id . '">' . esc_html($machine->name) . '</option>';
                    }
                    ?>
                </select>
                <button type="button" onclick="addItem('machine')">افزودن</button>
            </label>
            <ul id="machine_list"></ul>
            <input type="hidden" name="machines" id="machines_input">

            <h3>فرآیندها</h3>
            <label>فرآیند:
                <select id="process_select">
                    <option value="">انتخاب کنید</option>
                    <?php
                    $processes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}processes");
                    foreach ($processes as $process) {
                        echo '<option value="' . $process->id . '">' . esc_html($process->name) . '</option>';
                    }
                    ?>
                </select>
                <button type="button" onclick="addItem('process')">افزودن</button>
            </label>
            <ul id="process_list"></ul>
            <input type="hidden" name="processes" id="processes_input">

            <h3>مواد اولیه</h3>
            <label>ماده اولیه:
                <select id="material_select">
                    <option value="">انتخاب کنید</option>
                    <?php
                    $materials = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}materials");
                    foreach ($materials as $material) {
                        echo '<option value="' . $material->id . '">' . esc_html($material->name) . '</option>';
                    }
                    ?>
                </select>
            </label>
            <label>قیمت:
                <select id="material_price_select" disabled>
                    <option value="">انتخاب کنید</option>
                    <?php
                    $material_prices = $wpdb->get_results("SELECT mp.*, m.name as material_name FROM {$wpdb->prefix}material_prices mp JOIN {$wpdb->prefix}materials m ON mp.material_id = m.id");
                    foreach ($material_prices as $price) {
                        echo '<option value="' . $price->id . '" data-material-id="' . $price->material_id . '">' . esc_html($price->material_name) . ' - ' . number_format($price->price) . ' (تاریخ: ' . $price->purchase_date . ')</option>';
                    }
                    ?>
                </select>
            </label>
            <label>مقدار:
                <input type="number" id="material_quantity" step="0.01" disabled>
            </label>
            <label>ضایعات:
                <input type="number" id="material_waste" step="0.01" disabled>
            </label>
            <button type="button" onclick="addItem('material')">افزودن</button>
            <ul id="material_list"></ul>
            <input type="hidden" name="materials" id="materials_input">

            <h3>ملزومات بسته‌بندی</h3>
            <label>ملزوم:
                <select id="packaging_select">
                    <option value="">انتخاب کنید</option>
                    <?php
                    $packaging = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}packaging");
                    foreach ($packaging as $pack) {
                        echo '<option value="' . $pack->id . '">' . esc_html($pack->name) . '</option>';
                    }
                    ?>
                </select>
            </label>
            <label>قیمت:
                <select id="packaging_price_select" disabled>
                    <option value="">انتخاب کنید</option>
                    <?php
                    $packaging_prices = $wpdb->get_results("SELECT pp.*, p.name as packaging_name FROM {$wpdb->prefix}packaging_prices pp JOIN {$wpdb->prefix}packaging p ON pp.packaging_id = p.id");
                    foreach ($packaging_prices as $price) {
                        echo '<option value="' . $price->id . '" data-packaging-id="' . $price->packaging_id . '">' . esc_html($price->packaging_name) . ' - ' . number_format($price->price) . ' (تاریخ: ' . $price->purchase_date . ')</option>';
                    }
                    ?>
                </select>
            </label>
            <label>مقدار:
                <input type="number" id="packaging_quantity" step="0.01" disabled>
            </label>
            <label>ضایعات:
                <input type="number" id="packaging_waste" step="0.01" disabled>
            </label>
            <button type="button" onclick="addItem('packaging')">افزودن</button>
            <ul id="packaging_list"></ul>
            <input type="hidden" name="packaging" id="packaging_input">

            <h3>محصولات تولیدی</h3>
            <label>محصول:
                <select id="product_select">
                    <option value="">انتخاب کنید</option>
                    <?php
                    $products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}products");
                    foreach ($products as $product) {
                        echo '<option value="' . $product->id . '">' . esc_html($product->name) . '</option>';
                    }
                    ?>
                </select>
            </label>
            <label>مقدار:
                <input type="number" id="product_quantity" step="0.01" disabled>
            </label>
            <button type="button" onclick="addItem('product')">افزودن</button>
            <ul id="product_list"></ul>
            <input type="hidden" name="products" id="products_input">

            <h3>اقدامات عمومی</h3>
            <label>اقدام:
                <select id="general_action_select">
                    <option value="">انتخاب کنید</option>
                    <?php
                    $general_actions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}general_actions");
                    foreach ($general_actions as $action) {
                        echo '<option value="' . $action->id . '">' . esc_html($action->name) . '</option>';
                    }
                    ?>
                </select>
                <button type="button" onclick="addItem('general_action')">افزودن</button>
            </label>
            <ul id="general_action_list"></ul>
            <input type="hidden" name="general_actions" id="general_actions_input">

            <input type="submit" name="submit_production" value="ثبت تولید">
        </form>
    </div>
    <?php
    echo ob_get_clean();
}