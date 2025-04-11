document.addEventListener('DOMContentLoaded', function() {
    // به‌روزرسانی سری ساخت
    window.updateSerialCode = function() {
        const serialCodePart = document.getElementById('serial_code_part');
        const year = document.getElementById('year');
        const month = document.getElementById('month');
        const day = document.getElementById('day');
        if (serialCodePart && year && month && day) {
            if (serialCodePart.value && year.value && month.value && day.value) {
                const serialCode = `${serialCodePart.value}-${year.value}${month.value}${day.value}`;
                document.getElementById('generated_serial_code').textContent = serialCode;
            } else {
                document.getElementById('generated_serial_code').textContent = '';
            }
        }
    };

    // مدیریت انتخاب مواد اولیه و قیمت‌ها
    const materialSelect = document.getElementById('material_select');
    const materialPriceSelect = document.getElementById('material_price_select');
    const materialQuantity = document.getElementById('material_quantity');
    const materialWaste = document.getElementById('material_waste');

    if (materialSelect) {
        materialSelect.addEventListener('change', function() {
            const materialId = this.value;
            if (materialPriceSelect && materialQuantity && materialWaste) {
                materialPriceSelect.disabled = !materialId;
                materialQuantity.disabled = !materialId;
                materialWaste.disabled = !materialId;

                // فیلتر قیمت‌ها بر اساس ماده اولیه انتخاب‌شده
                Array.from(materialPriceSelect.options).forEach(option => {
                    if (option.value === '') {
                        return;
                    }
                    const optionMaterialId = option.getAttribute('data-material-id');
                    option.style.display = optionMaterialId === materialId || !materialId ? '' : 'none';
                });

                materialPriceSelect.value = '';
                materialQuantity.value = '';
                materialWaste.value = '';
            }
        });
    }

    // مدیریت انتخاب ملزومات بسته‌بندی و قیمت‌ها
    const packagingSelect = document.getElementById('packaging_select');
    const packagingPriceSelect = document.getElementById('packaging_price_select');
    const packagingQuantity = document.getElementById('packaging_quantity');
    const packagingWaste = document.getElementById('packaging_waste');

    if (packagingSelect) {
        packagingSelect.addEventListener('change', function() {
            const packagingId = this.value;
            if (packagingPriceSelect && packagingQuantity && packagingWaste) {
                packagingPriceSelect.disabled = !packagingId;
                packagingQuantity.disabled = !packagingId;
                packagingWaste.disabled = !packagingId;

                // فیلتر قیمت‌ها بر اساس ملزوم بسته‌بندی انتخاب‌شده
                Array.from(packagingPriceSelect.options).forEach(option => {
                    if (option.value === '') {
                        return;
                    }
                    const optionPackagingId = option.getAttribute('data-packaging-id');
                    option.style.display = optionPackagingId === packagingId || !packagingId ? '' : 'none';
                });

                packagingPriceSelect.value = '';
                packagingQuantity.value = '';
                packagingWaste.value = '';
            }
        });
    }

    // مدیریت انتخاب محصولات
    const productSelect = document.getElementById('product_select');
    const productQuantity = document.getElementById('product_quantity');

    if (productSelect) {
        productSelect.addEventListener('change', function() {
            const productId = this.value;
            if (productQuantity) {
                productQuantity.disabled = !productId;
                productQuantity.value = '';
            }
        });
    }

    // تابع برای افزودن آیتم‌ها
    window.addItem = function(type) {
        let select, quantityInput, wasteInput, priceSelect, list, input;
        if (type === 'worker') {
            select = document.getElementById('worker_select');
            list = document.getElementById('worker_list');
            input = document.getElementById('workers_input');
        } else if (type === 'machine') {
            select = document.getElementById('machine_select');
            list = document.getElementById('machine_list');
            input = document.getElementById('machines_input');
        } else if (type === 'process') {
            select = document.getElementById('process_select');
            list = document.getElementById('process_list');
            input = document.getElementById('processes_input');
        } else if (type === 'material') {
            select = document.getElementById('material_select');
            priceSelect = document.getElementById('material_price_select');
            quantityInput = document.getElementById('material_quantity');
            wasteInput = document.getElementById('material_waste');
            list = document.getElementById('material_list');
            input = document.getElementById('materials_input');
        } else if (type === 'packaging') {
            select = document.getElementById('packaging_select');
            priceSelect = document.getElementById('packaging_price_select');
            quantityInput = document.getElementById('packaging_quantity');
            wasteInput = document.getElementById('packaging_waste');
            list = document.getElementById('packaging_list');
            input = document.getElementById('packaging_input');
        } else if (type === 'product') {
            select = document.getElementById('product_select');
            quantityInput = document.getElementById('product_quantity');
            list = document.getElementById('product_list');
            input = document.getElementById('products_input');
        } else if (type === 'general_action') {
            select = document.getElementById('general_action_select');
            list = document.getElementById('general_action_list');
            input = document.getElementById('general_actions_input');
        }

        if (!select || !select.value) {
            alert('لطفاً یک گزینه انتخاب کنید.');
            return;
        }

        let item = { id: select.value };
        if (type === 'material' || type === 'packaging') {
            if (!priceSelect || !priceSelect.value || !quantityInput || !quantityInput.value) {
                alert('لطفاً قیمت و مقدار را وارد کنید.');
                return;
            }
            item.price_id = priceSelect.value;
            item.quantity = parseFloat(quantityInput.value);
            if (wasteInput && wasteInput.value) {
                item.waste = parseFloat(wasteInput.value);
            }
        } else if (type === 'product') {
            if (!quantityInput || !quantityInput.value) {
                alert('لطفاً مقدار را وارد کنید.');
                return;
            }
            item.quantity = parseFloat(quantityInput.value);
        }

        const items = input.value ? JSON.parse(input.value) : [];
        items.push(item);
        input.value = JSON.stringify(items);

        const li = document.createElement('li');
        li.textContent = select.options[select.selectedIndex].text;
        if (type === 'material' || type === 'packaging') {
            li.textContent += `: ${item.quantity}`;
            if (item.waste) {
                li.textContent += ` (ضایعات: ${item.waste})`;
            }
        } else if (type === 'product') {
            li.textContent += `: ${item.quantity}`;
        }
        const removeButton = document.createElement('button');
        removeButton.textContent = 'حذف';
        removeButton.onclick = function() {
            const index = items.indexOf(item);
            if (index > -1) {
                items.splice(index, 1);
                input.value = JSON.stringify(items);
                li.remove();
            }
        };
        li.appendChild(removeButton);
        list.appendChild(li);

        // بازنشانی فرم
        select.value = '';
        if (priceSelect) priceSelect.value = '';
        if (quantityInput) quantityInput.value = '';
        if (wasteInput) wasteInput.value = '';
        if (type === 'material') {
            if (materialPriceSelect && materialQuantity && materialWaste) {
                materialPriceSelect.disabled = true;
                materialQuantity.disabled = true;
                materialWaste.disabled = true;
            }
        } else if (type === 'packaging') {
            if (packagingPriceSelect && packagingQuantity && packagingWaste) {
                packagingPriceSelect.disabled = true;
                packagingQuantity.disabled = true;
                packagingWaste.disabled = true;
            }
        } else if (type === 'product') {
            if (productQuantity) {
                productQuantity.disabled = true;
            }
        }
    };
});