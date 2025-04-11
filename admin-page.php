<?php
/**
 * فایل مدیریت تنظیمات تولید
 * شامل تب‌های مختلف برای مدیریت مواد اولیه، محصولات، کارگران، و غیره
 */

function serial_production_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('<p>شما دسترسی به این بخش را ندارید.</p>');
    }

    global $wpdb;
    ob_start();
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'materials';
    $message = '';

    ?>
    <div class="wrap">
        <h1>مدیریت تولید</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=serial-production&tab=materials" class="nav-tab <?php echo $active_tab == 'materials' ? 'nav-tab-active' : ''; ?>">مواد اولیه</a>
            <a href="?page=serial-production&tab=material_prices" class="nav-tab <?php echo $active_tab == 'material_prices' ? 'nav-tab-active' : ''; ?>">قیمت مواد اولیه</a>
            <a href="?page=serial-production&tab=packaging" class="nav-tab <?php echo $active_tab == 'packaging' ? 'nav-tab-active' : ''; ?>">ملزومات بسته‌بندی</a>
            <a href="?page=serial-production&tab=packaging_prices" class="nav-tab <?php echo $active_tab == 'packaging_prices' ? 'nav-tab-active' : ''; ?>">قیمت ملزومات بسته‌بندی</a>
            <a href="?page=serial-production&tab=products" class="nav-tab <?php echo $active_tab == 'products' ? 'nav-tab-active' : ''; ?>">محصولات</a>
            <a href="?page=serial-production&tab=workers" class="nav-tab <?php echo $active_tab == 'workers' ? 'nav-tab-active' : ''; ?>">کارگران</a>
            <a href="?page=serial-production&tab=machines" class="nav-tab <?php echo $active_tab == 'machines' ? 'nav-tab-active' : ''; ?>">ماشین‌آلات</a>
            <a href="?page=serial-production&tab=processes" class="nav-tab <?php echo $active_tab == 'processes' ? 'nav-tab-active' : ''; ?>">فرآیندها</a>
            <a href="?page=serial-production&tab=general_actions" class="nav-tab <?php echo $active_tab == 'general_actions' ? 'nav-tab-active' : ''; ?>">اقدامات عمومی</a>
            <a href="?page=serial-production&tab=serial_codes" class="nav-tab <?php echo $active_tab == 'serial_codes' ? 'nav-tab-active' : ''; ?>">کدهای سری ساخت</a>
            <a href="?page=serial-production&tab=production_years" class="nav-tab <?php echo $active_tab == 'production_years' ? 'nav-tab-active' : ''; ?>">سال‌های تولید</a>
            <a href="?page=serial-production&tab=units" class="nav-tab <?php echo $active_tab == 'units' ? 'nav-tab-active' : ''; ?>">واحدها</a>
            <a href="?page=serial-production&tab=email" class="nav-tab <?php echo $active_tab == 'email' ? 'nav-tab-active' : ''; ?>">ایمیل</a>
        </h2>

        <?php
        if ($active_tab == 'materials') {
            if (isset($_POST['add_materials'])) {
                $name = sanitize_text_field($_POST['name']);
                $unit_id = intval($_POST['unit_id']);
                $quantity = floatval($_POST['initial_quantity']);
                $price = floatval($_POST['initial_price']);
                $purchase_date = sanitize_text_field($_POST['purchase_date']);

                $wpdb->insert($wpdb->prefix . 'materials', [
                    'name' => $name,
                    'unit_id' => $unit_id
                ]);
                $material_id = $wpdb->insert_id;

                if ($quantity > 0 && $price > 0) {
                    $wpdb->insert($wpdb->prefix . 'material_prices', [
                        'material_id' => $material_id,
                        'price' => $price,
                        'purchase_date' => $purchase_date
                    ]);
                    $price_id = $wpdb->insert_id;

                    $wpdb->insert($wpdb->prefix . 'material_inventory', [
                        'material_id' => $material_id,
                        'quantity' => $quantity,
                        'type' => 'added',
                        'price_id' => $price_id,
                        'user_id' => get_current_user_id(),
                        'transaction_date' => current_time('mysql')
                    ]);
                }

                $message = '<p>ماده اولیه با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_material'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'materials', ['id' => $id]);
                $message = '<p>ماده اولیه با موفقیت حذف شد.</p>';
            }

            $materials = $wpdb->get_results("SELECT m.*, u.name as unit_name FROM {$wpdb->prefix}materials m JOIN {$wpdb->prefix}units u ON m.unit_id = u.id");
            ?>
            <h2>مدیریت مواد اولیه</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>واحد</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($materials) {
                    foreach ($materials as $material) { ?>
                        <tr>
                            <td><?php echo esc_html($material->id); ?></td>
                            <td><?php echo esc_html($material->name); ?></td>
                            <td><?php echo esc_html($material->unit_name); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=materials&delete_material=1&id=<?php echo $material->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن ماده اولیه جدید</h3>
            <form method="post">
                <label>نام:
                    <input type="text" name="name" required>
                </label>
                <label>واحد:
                    <select name="unit_id" required>
                        <option value="">انتخاب کنید</option>
                        <?php
                        $units = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}units");
                        if ($units) {
                            foreach ($units as $unit) {
                                echo '<option value="' . $unit->id . '">' . esc_html($unit->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </label>
                <label>مقدار اولیه:
                    <input type="number" name="initial_quantity" step="0.01" value="0">
                </label>
                <label>قیمت اولیه (ریال):
                    <input type="number" name="initial_price" value="0">
                </label>
                <label>تاریخ خرید:
                    <input type="date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>">
                </label>
                <input type="submit" name="add_materials" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'material_prices') {
            if (isset($_POST['add_material_price'])) {
                $material_id = intval($_POST['material_id']);
                $price = floatval($_POST['price']);
                $purchase_date = sanitize_text_field($_POST['purchase_date']);
                $quantity = floatval($_POST['quantity']);

                $wpdb->insert($wpdb->prefix . 'material_prices', [
                    'material_id' => $material_id,
                    'price' => $price,
                    'purchase_date' => $purchase_date
                ]);
                $price_id = $wpdb->insert_id;

                if ($quantity > 0) {
                    $wpdb->insert($wpdb->prefix . 'material_inventory', [
                        'material_id' => $material_id,
                        'quantity' => $quantity,
                        'type' => 'added',
                        'price_id' => $price_id,
                        'user_id' => get_current_user_id(),
                        'transaction_date' => current_time('mysql')
                    ]);
                }

                $message = '<p>قیمت ماده اولیه با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_material_price'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'material_prices', ['id' => $id]);
                $message = '<p>قیمت ماده اولیه با موفقیت حذف شد.</p>';
            }

            $material_prices = $wpdb->get_results("SELECT mp.*, m.name as material_name FROM {$wpdb->prefix}material_prices mp JOIN {$wpdb->prefix}materials m ON mp.material_id = m.id");
            ?>
            <h2>مدیریت قیمت مواد اولیه</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>ماده اولیه</th>
                    <th>قیمت (ریال)</th>
                    <th>تاریخ خرید</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($material_prices) {
                    foreach ($material_prices as $price) { ?>
                        <tr>
                            <td><?php echo esc_html($price->id); ?></td>
                            <td><?php echo esc_html($price->material_name); ?></td>
                            <td><?php echo number_format($price->price); ?></td>
                            <td><?php echo esc_html($price->purchase_date); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=material_prices&delete_material_price=1&id=<?php echo $price->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن قیمت جدید</h3>
            <form method="post">
                <label>ماده اولیه:
                    <select name="material_id" required>
                        <option value="">انتخاب کنید</option>
                        <?php
                        $materials = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}materials");
                        if ($materials) {
                            foreach ($materials as $material) {
                                echo '<option value="' . $material->id . '">' . esc_html($material->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </label>
                <label>قیمت (ریال):
                    <input type="number" name="price" required>
                </label>
                <label>مقدار:
                    <input type="number" name="quantity" step="0.01" value="0">
                </label>
                <label>تاریخ خرید:
                    <input type="date" name="purchase_date" required>
                </label>
                <input type="submit" name="add_material_price" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'packaging') {
            if (isset($_POST['add_packaging'])) {
                $name = sanitize_text_field($_POST['name']);
                $unit_id = intval($_POST['unit_id']);
                $quantity = floatval($_POST['initial_quantity']);
                $price = floatval($_POST['initial_price']);
                $purchase_date = sanitize_text_field($_POST['purchase_date']);

                $wpdb->insert($wpdb->prefix . 'packaging', [
                    'name' => $name,
                    'unit_id' => $unit_id
                ]);
                $packaging_id = $wpdb->insert_id;

                if ($quantity > 0 && $price > 0) {
                    $wpdb->insert($wpdb->prefix . 'packaging_prices', [
                        'packaging_id' => $packaging_id,
                        'price' => $price,
                        'purchase_date' => $purchase_date
                    ]);
                    $price_id = $wpdb->insert_id;

                    $wpdb->insert($wpdb->prefix . 'packaging_inventory', [
                        'packaging_id' => $packaging_id,
                        'quantity' => $quantity,
                        'type' => 'added',
                        'price_id' => $price_id,
                        'user_id' => get_current_user_id(),
                        'transaction_date' => current_time('mysql')
                    ]);
                }

                $message = '<p>ملزوم بسته‌بندی با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_packaging'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'packaging', ['id' => $id]);
                $message = '<p>ملزوم بسته‌بندی با موفقیت حذف شد.</p>';
            }

            $packaging = $wpdb->get_results("SELECT p.*, u.name as unit_name FROM {$wpdb->prefix}packaging p JOIN {$wpdb->prefix}units u ON p.unit_id = u.id");
            ?>
            <h2>مدیریت ملزومات بسته‌بندی</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>واحد</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($packaging) {
                    foreach ($packaging as $pack) { ?>
                        <tr>
                            <td><?php echo esc_html($pack->id); ?></td>
                            <td><?php echo esc_html($pack->name); ?></td>
                            <td><?php echo esc_html($pack->unit_name); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=packaging&delete_packaging=1&id=<?php echo $pack->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن ملزوم بسته‌بندی جدید</h3>
            <form method="post">
                <label>نام:
                    <input type="text" name="name" required>
                </label>
                <label>واحد:
                    <select name="unit_id" required>
                        <option value="">انتخاب کنید</option>
                        <?php
                        $units = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}units");
                        if ($units) {
                            foreach ($units as $unit) {
                                echo '<option value="' . $unit->id . '">' . esc_html($unit->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </label>
                <label>مقدار اولیه:
                    <input type="number" name="initial_quantity" step="0.01" value="0">
                </label>
                <label>قیمت اولیه (ریال):
                    <input type="number" name="initial_price" value="0">
                </label>
                <label>تاریخ خرید:
                    <input type="date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>">
                </label>
                <input type="submit" name="add_packaging" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'packaging_prices') {
            if (isset($_POST['add_packaging_price'])) {
                $packaging_id = intval($_POST['packaging_id']);
                $price = floatval($_POST['price']);
                $purchase_date = sanitize_text_field($_POST['purchase_date']);
               $quantity = floatval($_POST['quantity']);

                $wpdb->insert($wpdb->prefix . 'packaging_prices', [
                    'packaging_id' => $packaging_id,
                    'price' => $price,
                    'purchase_date' => $purchase_date
                ]);
                $price_id = $wpdb->insert_id;

                if ($quantity > 0) {
                    $wpdb->insert($wpdb->prefix . 'packaging_inventory', [
                        'packaging_id' => $packaging_id,
                        'quantity' => $quantity,
                        'type' => 'added',
                        'price_id' => $price_id,
                        'user_id' => get_current_user_id(),
                        'transaction_date' => current_time('mysql')
                    ]);
                }

                $message = '<p>قیمت ملزوم بسته‌بندی با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_packaging_price'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'packaging_prices', ['id' => $id]);
                $message = '<p>قیمت ملزوم بسته‌بندی با موفقیت حذف شد.</p>';
            }

            $packaging_prices = $wpdb->get_results("SELECT pp.*, p.name as packaging_name FROM {$wpdb->prefix}packaging_prices pp JOIN {$wpdb->prefix}packaging p ON pp.packaging_id = p.id");
            ?>
            <h2>مدیریت قیمت ملزومات بسته‌بندی</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>ملزوم بسته‌بندی</th>
                    <th>قیمت (ریال)</th>
                    <th>تاریخ خرید</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($packaging_prices) {
                    foreach ($packaging_prices as $price) { ?>
                        <tr>
                            <td><?php echo esc_html($price->id); ?></td>
                            <td><?php echo esc_html($price->packaging_name); ?></td>
                            <td><?php echo number_format($price->price); ?></td>
                            <td><?php echo esc_html($price->purchase_date); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=packaging_prices&delete_packaging_price=1&id=<?php echo $price->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن قیمت جدید</h3>
            <form method="post">
                <label>ملزوم بسته‌بندی:
                    <select name="packaging_id" required>
                        <option value="">انتخاب کنید</option>
                        <?php
                        $packaging = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}packaging");
                        if ($packaging) {
                            foreach ($packaging as $pack) {
                                echo '<option value="' . $pack->id . '">' . esc_html($pack->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </label>
                <label>قیمت (ریال):
                    <input type="number" name="price" required>
                </label>
                <label>مقدار:
                    <input type="number" name="quantity" step="0.01" value="0">
                </label>
                <label>تاریخ خرید:
                    <input type="date" name="purchase_date" required>
                </label>
                <input type="submit" name="add_packaging_price" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'products') {
            if (isset($_POST['add_product'])) {
                $name = sanitize_text_field($_POST['name']);
                $unit_id = intval($_POST['unit_id']);
                $wpdb->insert($wpdb->prefix . 'products', [
                    'name' => $name,
                    'unit_id' => $unit_id
                ]);
                $message = '<p>محصول با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_product'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'products', ['id' => $id]);
                $message = '<p>محصول با موفقیت حذف شد.</p>';
            }

            $products = $wpdb->get_results("SELECT p.*, u.name as unit_name FROM {$wpdb->prefix}products p JOIN {$wpdb->prefix}units u ON p.unit_id = u.id");
            ?>
            <h2>مدیریت محصولات</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>واحد</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($products) {
                    foreach ($products as $product) { ?>
                        <tr>
                            <td><?php echo esc_html($product->id); ?></td>
                            <td><?php echo esc_html($product->name); ?></td>
                            <td><?php echo esc_html($product->unit_name); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=products&delete_product=1&id=<?php echo $product->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن محصول جدید</h3>
            <form method="post">
                <label>نام:
                    <input type="text" name="name" required>
                </label>
                <label>واحد:
                    <select name="unit_id" required>
                        <option value="">انتخاب کنید</option>
                        <?php
                        $units = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}units");
                        if ($units) {
                            foreach ($units as $unit) {
                                echo '<option value="' . $unit->id . '">' . esc_html($unit->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </label>
                <input type="submit" name="add_product" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'workers') {
            if (isset($_POST['add_worker'])) {
                $name = sanitize_text_field($_POST['name']);
                $position = sanitize_text_field($_POST['position']);
                $wpdb->insert($wpdb->prefix . 'workers', [
                    'name' => $name,
                    'position' => $position
                ]);
                $message = '<p>کارگر با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_worker'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'workers', ['id' => $id]);
                $message = '<p>کارگر با موفقیت حذف شد.</p>';
            }

            $workers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}workers");
            ?>
            <h2>مدیریت کارگران</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>سمت</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($workers) {
                    foreach ($workers as $worker) { ?>
                        <tr>
                            <td><?php echo esc_html($worker->id); ?></td>
                            <td><?php echo esc_html($worker->name); ?></td>
                            <td><?php echo esc_html($worker->position); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=workers&delete_worker=1&id=<?php echo $worker->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن کارگر جدید</h3>
            <form method="post">
                <label>نام:
                    <input type="text" name="name" required>
                </label>
                <label>سمت:
                    <input type="text" name="position" required>
                </label>
                <input type="submit" name="add_worker" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'machines') {
            if (isset($_POST['add_machine'])) {
                $name = sanitize_text_field($_POST['name']);
                $wpdb->insert($wpdb->prefix . 'machines', [
                    'name' => $name
                ]);
                $message = '<p>ماشین‌آلات با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_machine'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'machines', ['id' => $id]);
                $message = '<p>ماشین‌آلات با موفقیت حذف شد.</p>';
            }

            $machines = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}machines");
            ?>
            <h2>مدیریت ماشین‌آلات</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($machines) {
                    foreach ($machines as $machine) { ?>
                        <tr>
                            <td><?php echo esc_html($machine->id); ?></td>
                            <td><?php echo esc_html($machine->name); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=machines&delete_machine=1&id=<?php echo $machine->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن ماشین‌آلات جدید</h3>
            <form method="post">
                <label>نام:
                    <input type="text" name="name" required>
                </label>
                <input type="submit" name="add_machine" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'processes') {
            if (isset($_POST['add_process'])) {
                $name = sanitize_text_field($_POST['name']);
                $wpdb->insert($wpdb->prefix . 'processes', [
                    'name' => $name
                ]);
                $message = '<p>فرآیند با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_process'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'processes', ['id' => $id]);
                $message = '<p>فرآیند با موفقیت حذف شد.</p>';
            }

            $processes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}processes");
            ?>
            <h2>مدیریت فرآیندها</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($processes) {
                    foreach ($processes as $process) { ?>
                        <tr>
                            <td><?php echo esc_html($process->id); ?></td>
                            <td><?php echo esc_html($process->name); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=processes&delete_process=1&id=<?php echo $process->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن فرآیند جدید</h3>
            <form method="post">
                <label>نام:
                    <input type="text" name="name" required>
                </label>
                <input type="submit" name="add_process" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'general_actions') {
            if (isset($_POST['add_general_action'])) {
                $name = sanitize_text_field($_POST['name']);
                $wpdb->insert($wpdb->prefix . 'general_actions', [
                    'name' => $name
                ]);
                $message = '<p>اقدام عمومی با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_general_action'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'general_actions', ['id' => $id]);
                $message = '<p>اقدام عمومی با موفقیت حذف شد.</p>';
            }

            $general_actions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}general_actions");
            ?>
            <h2>مدیریت اقدامات عمومی</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($general_actions) {
                    foreach ($general_actions as $action) { ?>
                        <tr>
                            <td><?php echo esc_html($action->id); ?></td>
                            <td><?php echo esc_html($action->name); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=general_actions&delete_general_action=1&id=<?php echo $action->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن اقدام عمومی جدید</h3>
            <form method="post">
                               <label>نام:
                    <input type="text" name="name" required>
                </label>
                <input type="submit" name="add_general_action" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'serial_codes') {
            if (isset($_POST['add_serial_code'])) {
                $name = sanitize_text_field($_POST['name']);
                $code = sanitize_text_field($_POST['code']);
                $wpdb->insert($wpdb->prefix . 'serial_codes', [
                    'name' => $name,
                    'code' => $code
                ]);
                $message = '<p>کد سری ساخت با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_serial_code'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'serial_codes', ['id' => $id]);
                $message = '<p>کد سری ساخت با موفقیت حذف شد.</p>';
            }

            $serial_codes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}serial_codes");
            ?>
            <h2>مدیریت کدهای سری ساخت</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>کد</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($serial_codes) {
                    foreach ($serial_codes as $serial_code) { ?>
                        <tr>
                            <td><?php echo esc_html($serial_code->id); ?></td>
                            <td><?php echo esc_html($serial_code->name); ?></td>
                            <td><?php echo esc_html($serial_code->code); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=serial_codes&delete_serial_code=1&id=<?php echo $serial_code->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن کد سری ساخت جدید</h3>
            <form method="post">
                <label>نام:
                    <input type="text" name="name" required>
                </label>
                <label>کد:
                    <input type="text" name="code" required>
                </label>
                <input type="submit" name="add_serial_code" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'production_years') {
            if (isset($_POST['add_production_year'])) {
                $year = intval($_POST['year']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                if ($is_active) {
                    $wpdb->update($wpdb->prefix . 'production_years', ['is_active' => 0], ['is_active' => 1]);
                }

                $wpdb->insert($wpdb->prefix . 'production_years', [
                    'year' => $year,
                    'is_active' => $is_active
                ]);
                $message = '<p>سال تولید با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_production_year'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'production_years', ['id' => $id]);
                $message = '<p>سال تولید با موفقیت حذف شد.</p>';
            }

            if (isset($_GET['set_active_year'])) {
                $id = intval($_GET['id']);
                $wpdb->update($wpdb->prefix . 'production_years', ['is_active' => 0], ['is_active' => 1]);
                $wpdb->update($wpdb->prefix . 'production_years', ['is_active' => 1], ['id' => $id]);
                $message = '<p>سال تولید فعال با موفقیت تغییر کرد.</p>';
            }

            $production_years = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}production_years");
            ?>
            <h2>مدیریت سال‌های تولید</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>سال</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($production_years) {
                    foreach ($production_years as $year) { ?>
                        <tr>
                            <td><?php echo esc_html($year->id); ?></td>
                            <td><?php echo esc_html($year->year); ?></td>
                            <td><?php echo $year->is_active ? 'فعال' : 'غیرفعال'; ?></td>
                            <td>
                                <?php if (!$year->is_active) { ?>
                                    <a href="?page=serial-production&tab=production_years&set_active_year=1&id=<?php echo $year->id; ?>">فعال کردن</a> |
                                <?php } ?>
                                <a href="?page=serial-production&tab=production_years&delete_production_year=1&id=<?php echo $year->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن سال تولید جدید</h3>
            <form method="post">
                <label>سال:
                    <input type="number" name="year" required>
                </label>
                <label>فعال:
                    <input type="checkbox" name="is_active" value="1">
                </label>
                <input type="submit" name="add_production_year" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'units') {
            if (isset($_POST['add_unit'])) {
                $name = sanitize_text_field($_POST['name']);
                $wpdb->insert($wpdb->prefix . 'units', [
                    'name' => $name
                ]);
                $message = '<p>واحد با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_unit'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'units', ['id' => $id]);
                $message = '<p>واحد با موفقیت حذف شد.</p>';
            }

            $units = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}units");
            ?>
            <h2>مدیریت واحدها</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($units) {
                    foreach ($units as $unit) { ?>
                        <tr>
                            <td><?php echo esc_html($unit->id); ?></td>
                            <td><?php echo esc_html($unit->name); ?></td>
                            <td>
                                <a href="?page=serial-production&tab=units&delete_unit=1&id=<?php echo $unit->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن واحد جدید</h3>
            <form method="post">
                <label>نام:
                    <input type="text" name="name" required>
                </label>
                <input type="submit" name="add_unit" value="افزودن">
            </form>
            <?php
        } elseif ($active_tab == 'email') {
            if (isset($_POST['add_email'])) {
                $name = sanitize_text_field($_POST['name']);
                $email = sanitize_email($_POST['email']);
                $wpdb->insert($wpdb->prefix . 'email_destinations', [
                    'name' => $name,
                    'email' => $email
                ]);
                $message = '<p>ایمیل با موفقیت اضافه شد.</p>';
            }

            if (isset($_GET['delete_email'])) {
                $id = intval($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'email_destinations', ['id' => $id]);
                $message = '<p>ایمیل با موفقیت حذف شد.</p>';
            }

            if (isset($_POST['edit_email'])) {
                $id = intval($_POST['email_id']);
                $name = sanitize_text_field($_POST['name']);
                $email = sanitize_email($_POST['email']);
                $wpdb->update($wpdb->prefix . 'email_destinations', [
                    'name' => $name,
                    'email' => $email
                ], ['id' => $id]);
                $message = '<p>ایمیل با موفقیت ویرایش شد.</p>';
            }

            $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}email_destinations");
            ?>
            <h2>مدیریت ایمیل‌ها</h2>
            <?php if ($message) echo $message; ?>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>ایمیل</th>
                    <th>عملیات</th>
                </tr>
                <?php if ($emails) {
                    foreach ($emails as $email) { ?>
                        <tr>
                            <td><?php echo esc_html($email->id); ?></td>
                            <td><?php echo esc_html($email->name); ?></td>
                            <td><?php echo esc_html($email->email); ?></td>
                            <td>
                                <a href="#" onclick="editEmail(<?php echo $email->id; ?>, '<?php echo esc_js($email->name); ?>', '<?php echo esc_js($email->email); ?>')">ویرایش</a> |
                                <a href="?page=serial-production&tab=email&delete_email=1&id=<?php echo $email->id; ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>

            <h3>افزودن ایمیل جدید</h3>
            <form method="post" id="email_form">
                <input type="hidden" name="email_id" id="email_id">
                <label>نام:
                    <input type="text" name="name" id="email_name" required>
                </label>
                <label>ایمیل:
                    <input type="email" name="email" id="email_address" required>
                </label>
                <input type="submit" name="add_email" id="email_submit" value="افزودن">
            </form>
            <script>
                function editEmail(id, name, email) {
                    document.getElementById('email_id').value = id;
                    document.getElementById('email_name').value = name;
                    document.getElementById('email_address').value = email;
                    document.getElementById('email_submit').value = 'ویرایش';
                    document.getElementById('email_form').setAttribute('name', 'edit_email');
                }
            </script>
            <?php
        }
        ?>
    </div>
    <?php
    echo ob_get_clean();
}