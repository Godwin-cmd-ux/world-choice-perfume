{{-- Sale page logic. Params: $accent (e.g. 'amber'), $hasDiscount (bool, stock-manager) --}}
<script>
    const ACCENT = '{{ $accent }}';
    let paymentRowIndex = 1;
    let bottleRowIndex = 1;

    // ===================== SALE TYPE =====================
    function accentActive() { return 'flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-' + ACCENT + '-600 text-white shadow'; }
    function accentIdle() { return 'flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-gray-200'; }

    function setSaleType(type) {
        document.getElementById('sale_type').value = type;
        document.getElementById('tab-retail').className = type === 'retail' ? accentActive() : accentIdle();
        document.getElementById('tab-wholesale').className = type === 'wholesale' ? accentActive() : accentIdle();
        document.getElementById('sale-type-hint').innerHTML = type === 'wholesale'
            ? '<i class="fas fa-info-circle mr-1"></i> Custom prices allowed'
            : '<i class="fas fa-info-circle mr-1"></i> Fixed selling prices';

        // Empty bottles are never sold in retail mode
        const bottleCard = document.getElementById('empty-bottles-card');
        if (bottleCard) {
            bottleCard.classList.toggle('hidden', type !== 'wholesale');
            if (type !== 'wholesale') resetBottles();
        }

        document.querySelectorAll('.cart-custom-price').forEach(el => el.classList.toggle('hidden', type !== 'wholesale'));
        @if (!empty($hasDiscount))
        document.querySelectorAll('.cart-discount-price').forEach(el => el.classList.toggle('hidden', type !== 'retail'));
        @endif
        calculateTotal();
    }

    function resetBottles() {
        const container = document.getElementById('bottle-items-container');
        const first = container.querySelector('.bottle-item-row');
        container.querySelectorAll('.bottle-item-row').forEach((r, i) => { if (i > 0) r.remove(); });
        first.querySelector('.bottle-volume-select').value = '';
        first.querySelector('.bottle-qty-input').value = 1;
        first.querySelector('.bottle-price-input').value = '';
        first.querySelector('.bottle-line-total').textContent = formatMoney(0);
        first.querySelectorAll('select, input').forEach(el => { if (el.name) el.name = el.name.replace(/\d+/, 0); });
        bottleRowIndex = 1;
    }

    // ===================== CUSTOMER SEARCH =====================
    const customerSearch = document.getElementById('customerSearch');
    const customerResults = document.getElementById('customerResults');
    let searchTimeout;

    customerSearch.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) { customerResults.classList.add('hidden'); return; }
        searchTimeout = setTimeout(() => {
            fetch(`{{ route('api.customers.search') }}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.length === 0) {
                        customerResults.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No customers found. <button type="button" onclick="createCustomerFromSearch()" class="text-' + ACCENT + '-700 hover:underline">Create new</button></div>';
                    } else {
                        customerResults.innerHTML = data.map(c =>
                            `<div class="px-3 py-2 text-sm hover:bg-${ACCENT}-50 cursor-pointer border-b last:border-0" onclick="selectCustomer(${c.id}, '${(c.name || '').replace(/'/g, "\\'")}', '${(c.phone || '').replace(/'/g, "\\'")}')">
                                <span class="font-medium">${c.name || 'Unnamed'}</span>
                                ${c.phone ? `<span class="text-gray-500 ml-2">${c.phone}</span>` : ''}
                            </div>`
                        ).join('');
                    }
                    customerResults.classList.remove('hidden');
                });
        }, 300);
    });

    function selectCustomer(id, name, phone) {
        document.getElementById('customer_id').value = id;
        document.getElementById('selectedCustomerName').textContent = name;
        document.getElementById('selectedCustomerPhone').textContent = phone || '';
        document.getElementById('selectedCustomer').classList.remove('hidden');
        document.getElementById('newCustomerForm').classList.add('hidden');
        document.getElementById('newCustomerBtn').classList.add('hidden');
        customerSearch.value = '';
        customerResults.classList.add('hidden');
        document.getElementById('newCustomerName').value = '';
        document.getElementById('newCustomerPhone').value = '';
    }

    function clearCustomer() {
        document.getElementById('customer_id').value = '';
        document.getElementById('selectedCustomer').classList.add('hidden');
        document.getElementById('newCustomerBtn').classList.remove('hidden');
        document.getElementById('newCustomerForm').classList.add('hidden');
    }

    function toggleNewCustomer() {
        const form = document.getElementById('newCustomerForm');
        form.classList.toggle('hidden');
        if (!form.classList.contains('hidden')) {
            document.getElementById('selectedCustomer').classList.add('hidden');
            document.getElementById('customer_id').value = '';
        }
    }

    function createCustomerFromSearch() {
        const q = customerSearch.value.trim();
        document.getElementById('newCustomerForm').classList.remove('hidden');
        document.getElementById('newCustomerName').value = q;
        document.getElementById('customer_id').value = '';
        customerResults.classList.add('hidden');
        customerSearch.value = '';
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#customerSearch') && !e.target.closest('#customerResults')) {
            customerResults.classList.add('hidden');
        }
    });

    // ===================== PRODUCT PICKER (searchable checkboxes) =====================
    const productSearch = document.getElementById('productSearch');

    function filterProducts() {
        const q = (productSearch.value || '').trim().toLowerCase();
        let visible = 0;
        document.querySelectorAll('.product-pick').forEach(label => {
            const name = (label.querySelector('.product-check').dataset.name || '').toLowerCase();
            const show = !q || name.includes(q);
            label.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        document.getElementById('product-no-match').classList.toggle('hidden', visible > 0);
    }
    productSearch.addEventListener('input', filterProducts);

    document.querySelectorAll('.product-check').forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked) addToCart(this);
            else removeFromCart(this.value);
        });
        const label = cb.closest('label');
        if (label) {
            label.addEventListener('click', function (e) {
                if (e.target.classList.contains('product-check')) return;
                e.preventDefault();
                const box = this.querySelector('.product-check');
                box.checked = !box.checked;
                box.dispatchEvent(new Event('change'));
            });
        }
    });

    function formatMoney(n) { return 'TZS ' + Number(n).toLocaleString(); }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function cartCount() { return document.querySelectorAll('.cart-row').length; }

    function addToCart(cb) {
        const cart = document.getElementById('cart-rows');
        if (cart.querySelector('.cart-row[data-product-id="' + cb.value + '"]')) return;

        const tr = document.createElement('tr');
        tr.className = 'cart-row';
        tr.dataset.productId = cb.value;
        tr.innerHTML =
            '<td class="px-3 py-2">' +
                '<span class="font-medium text-sm block">' + escapeHtml(cb.dataset.name) + '</span>' +
                '<span class="text-xs text-gray-500">' + Number(cb.dataset.stock) + ' in stock</span>' +
                '<input type="hidden" class="cart-product-hidden">' +
                '<input type="hidden" class="cart-qty-hidden" value="1">' +
            '</td>' +
            '<td class="px-3 py-2">' +
                '<span class="cart-unit-price text-sm block"></span>' +
                @if (!empty($hasDiscount))
                '<input type="number" step="0.01" min="0" class="cart-discount-price hidden mt-1 w-28 px-2 py-1 border border-dashed rounded text-xs" placeholder="Custom price">' +
                @endif
                '<input type="number" step="0.01" min="0" class="cart-custom-price hidden mt-1 w-28 px-2 py-1 border rounded text-xs" placeholder="Custom price">' +
            '</td>' +
            '<td class="px-3 py-2 text-center whitespace-nowrap">' +
                '<div class="inline-flex items-center border rounded-lg">' +
                    '<button type="button" class="cart-dec w-7 h-7 text-gray-600 hover:bg-gray-100 rounded-l-lg" onclick="changeQty(this, -1)">&minus;</button>' +
                    '<span class="cart-qty w-8 text-center text-sm font-medium">1</span>' +
                    '<button type="button" class="cart-inc w-7 h-7 text-gray-600 hover:bg-gray-100 rounded-r-lg" onclick="changeQty(this, 1)">+</button>' +
                '</div>' +
            '</td>' +
            '<td class="px-3 py-2 text-right cart-line-total text-sm font-medium">' + formatMoney(0) + '</td>' +
            '<td class="px-2 py-2 text-center">' +
                '<button type="button" class="text-red-500 hover:text-red-700" title="Remove" onclick="removeFromCart(' + cb.value + ')"><i class="fas fa-times"></i></button>' +
            '</td>';

        cart.appendChild(tr);
        document.getElementById('cart-empty').classList.add('hidden');

        const custom = tr.querySelector('.cart-custom-price');
        custom.addEventListener('input', calculateTotal);
        custom.classList.toggle('hidden', document.getElementById('sale_type').value !== 'wholesale');
        @if (!empty($hasDiscount))
        const discount = tr.querySelector('.cart-discount-price');
        discount.addEventListener('input', calculateTotal);
        discount.classList.toggle('hidden', document.getElementById('sale_type').value !== 'retail');
        @endif

        reindex();
        calculateTotal();
    }

    function removeFromCart(productId) {
        const row = document.getElementById('cart-rows').querySelector('.cart-row[data-product-id="' + productId + '"]');
        if (row) row.remove();
        const cb = document.querySelector('.product-check[value="' + productId + '"]');
        if (cb) cb.checked = false;
        if (cartCount() === 0) document.getElementById('cart-empty').classList.remove('hidden');
        reindex();
        calculateTotal();
    }

    function changeQty(btn, delta) {
        const row = btn.closest('.cart-row');
        const qtyEl = row.querySelector('.cart-qty');
        let q = parseInt(qtyEl.textContent) + delta;
        if (q < 1) q = 1;
        qtyEl.textContent = q;
        row.querySelector('.cart-qty-hidden').value = q;
        calculateTotal();
    }

    function reindex() {
        document.querySelectorAll('.cart-row').forEach((row, i) => {
            row.querySelector('.cart-product-hidden').value = row.dataset.productId;
            row.querySelector('.cart-product-hidden').name = 'items[' + i + '][product_id]';
            row.querySelector('.cart-qty-hidden').name = 'items[' + i + '][quantity]';
            const cp = row.querySelector('.cart-custom-price');
            if (cp) cp.name = 'items[' + i + '][custom_price]';
            @if (!empty($hasDiscount))
            const dp = row.querySelector('.cart-discount-price');
            if (dp) dp.name = 'items[' + i + '][discount_price]';
            @endif
        });
    }

    // ===================== TOTALS =====================
    function computeSaleTotal() {
        let total = 0;
        const type = document.getElementById('sale_type').value;
        const isWholesale = type === 'wholesale';
        const isRetail = type === 'retail';
        document.querySelectorAll('.cart-row').forEach(row => {
            const cb = document.querySelector('.product-check[value="' + row.dataset.productId + '"]');
            const price = parseFloat(cb ? cb.dataset.price : 0);
            const qty = parseInt(row.querySelector('.cart-qty-hidden').value || 0);
            const custom = row.querySelector('.cart-custom-price');
            let unit = price;
            if (isWholesale && custom && !custom.classList.contains('hidden') && custom.value) unit = parseFloat(custom.value) || price;
            @if (!empty($hasDiscount))
            const discount = row.querySelector('.cart-discount-price');
            if (isRetail && discount && !discount.classList.contains('hidden') && discount.value) unit = parseFloat(discount.value) || price;
            @endif
            const lt = unit * qty;
            row.querySelector('.cart-line-total').textContent = formatMoney(lt);
            row.querySelector('.cart-unit-price').textContent = unit.toLocaleString();
            total += lt;
        });
        return total + calculateBottleTotal();
    }

    function calculateTotal() {
        document.getElementById('grand-total').textContent = formatMoney(computeSaleTotal());
        validatePayments();
    }

    // ===================== PAYMENT MODE =====================
    function togglePaymentMode(mode) {
        document.querySelectorAll('.payment-mode-option').forEach(el => el.classList.remove('border-' + ACCENT + '-500', 'bg-' + ACCENT + '-50'));
        document.querySelector('.payment-mode-option[data-mode="' + mode + '"]').classList.add('border-' + ACCENT + '-500', 'bg-' + ACCENT + '-50');
        const single = document.getElementById('singlePayment');
        const multi = document.getElementById('multiPayment');
        single.classList.toggle('hidden', mode !== 'single');
        multi.classList.toggle('hidden', mode !== 'multi');
        single.querySelectorAll('select, input').forEach(el => el.disabled = mode !== 'single');
        multi.querySelectorAll('select, input').forEach(el => el.disabled = mode !== 'multi');
        if (mode === 'multi') validatePayments();
        else { document.getElementById('submitBtn').disabled = false; document.getElementById('submitBtn').classList.remove('opacity-50'); }
    }

    function addPaymentRow() {
        const container = document.getElementById('payment-rows');
        const firstRow = container.querySelector('.payment-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('select, input').forEach(el => { if (el.name) el.name = el.name.replace(/\d+/, paymentRowIndex); });
        newRow.querySelector('.payment-amount').value = '';
        container.appendChild(newRow);
        paymentRowIndex++;
    }

    function removePaymentRow(btn) {
        if (document.getElementById('payment-rows').children.length > 1) { btn.closest('.payment-row').remove(); validatePayments(); }
    }

    function validatePayments() {
        const mode = document.querySelector('input[name="payment_mode"]:checked')?.value;
        if (mode !== 'multi') return;
        const total = computeSaleTotal();
        let paymentTotal = 0;
        document.querySelectorAll('.payment-amount').forEach(input => { paymentTotal += parseFloat(input.value || 0); });
        document.getElementById('payment-total').textContent = formatMoney(paymentTotal);
        const mismatch = Math.abs(paymentTotal - total) > 0.01;
        document.getElementById('payment-mismatch').classList.toggle('hidden', !mismatch);
        document.getElementById('submitBtn').disabled = mismatch;
        document.getElementById('submitBtn').classList.toggle('opacity-50', mismatch);
    }

    // ===================== EMPTY BOTTLES =====================
    const BOTTLE_VARIANTS = @json($bottleVariants ?? []);
    const BOTTLE_DETAIL_VOLUMES = ['30', '50', '100'];
    const BOTTLE_VARIANT_LABELS = {
        'box_logo_yellow': 'With Box · With Logo · Yellow',
        'box_logo_black': 'With Box · With Logo · Black',
        'box_nologo_black': 'With Box · No Logo · Black',
        'box_nologo_white': 'With Box · No Logo · White',
        'no_box': 'Without Box',
        'plain': 'Unclassified',
    };

    function refreshBottleVariant(row) {
        const volSelect = row.querySelector('.bottle-volume-select');
        const vSelect = row.querySelector('.bottle-variant-select');
        if (!vSelect) return;
        const vol = volSelect.value;

        if (!BOTTLE_DETAIL_VOLUMES.includes(String(vol))) {
            vSelect.classList.add('hidden');
            vSelect.value = '';
            return;
        }

        const buckets = BOTTLE_VARIANTS[vol] || {};
        let html = '<option value="">Select box / logo / color</option>';
        const keys = ['box_logo_yellow', 'box_logo_black', 'box_nologo_black', 'box_nologo_white', 'no_box', 'plain'];
        keys.forEach(k => {
            const q = buckets[k] || 0;
            if (q > 0) {
                html += `<option value="${k}">${BOTTLE_VARIANT_LABELS[k] || k} (${q} in stock)</option>`;
            }
        });
        vSelect.innerHTML = html;
        vSelect.classList.remove('hidden');
    }

    function addBottleRow() {
        const container = document.getElementById('bottle-items-container');
        const firstRow = container.querySelector('.bottle-item-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('select, input').forEach(el => { if (el.name) el.name = el.name.replace(/\d+/, bottleRowIndex); });
        newRow.querySelector('.bottle-qty-input').value = 1;
        newRow.querySelector('.bottle-price-input').value = '';
        newRow.querySelector('.bottle-line-total').textContent = formatMoney(0);
        newRow.querySelector('.bottle-volume-select').value = '';
        newRow.querySelector('.bottle-variant-select').value = '';
        container.appendChild(newRow);
        bottleRowIndex++;
        bindBottleEvents();
        refreshBottleVariant(newRow);
    }

    function removeBottleRow(btn) {
        if (document.getElementById('bottle-items-container').children.length > 1) {
            btn.closest('.bottle-item-row').remove();
            calculateTotal();
        }
    }

    function bindBottleEvents() {
        document.querySelectorAll('.bottle-qty-input').forEach(input => { input.addEventListener('input', calculateTotal); });
        document.querySelectorAll('.bottle-price-input').forEach(input => { input.addEventListener('input', calculateTotal); });
        document.querySelectorAll('.bottle-volume-select').forEach(select => {
            select.addEventListener('change', () => refreshBottleVariant(select.closest('.bottle-item-row')));
        });
    }

    function calculateBottleTotal() {
        let total = 0;
        document.querySelectorAll('.bottle-item-row').forEach(row => {
            const qty = parseInt(row.querySelector('.bottle-qty-input').value || 0);
            const price = parseFloat(row.querySelector('.bottle-price-input').value || 0);
            const lineTotal = qty * price;
            row.querySelector('.bottle-line-total').textContent = formatMoney(lineTotal);
            total += lineTotal;
        });
        return total;
    }

    // ===================== INIT =====================
    bindBottleEvents();
    document.querySelectorAll('.bottle-item-row').forEach(row => refreshBottleVariant(row));
    togglePaymentMode('single');
    setSaleType(document.getElementById('sale_type').value);
</script>