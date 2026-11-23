{{-- Sale page logic. Params: $accent (e.g. 'amber'), $hasDiscount (bool, stock-manager) --}}
<script>
    const ACCENT = '{{ $accent }}';
    let paymentRowIndex = 1;
    let bottleRowIndex = 1;

    // Every product line carries its own identity: plain products have an
    // empty volume/variant, a variety line is pre-bound to its bottling, so
    // the cart never has to ask which bottling is being sold.

    // ===================== SALE TYPE =====================
    function accentActive() { return 'flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-' + ACCENT + '-600 text-white shadow'; }
    function accentIdle() { return 'flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-' + ACCENT + '-100 hover:text-' + ACCENT + '-700'; }

    function setSaleType(type) {
        document.getElementById('sale_type').value = type;
        document.getElementById('tab-retail').className = type === 'retail' ? accentActive() : accentIdle();
        document.getElementById('tab-wholesale').className = type === 'wholesale' ? accentActive() : accentIdle();
        document.getElementById('sale-type-hint').innerHTML =
            '<i class="fas fa-info-circle mr-1"></i> Custom prices allowed';

        // Empty bottles are never sold in retail mode
        const bottleCard = document.getElementById('empty-bottles-card');
        if (bottleCard) {
            bottleCard.classList.toggle('hidden', type !== 'wholesale');
            if (type !== 'wholesale') resetBottles();
        }

        @if (!empty($hasDiscount))
        document.querySelectorAll('.cart-custom-price').forEach(el => el.classList.toggle('hidden', type !== 'wholesale'));
        document.querySelectorAll('.cart-discount-price').forEach(el => el.classList.toggle('hidden', type !== 'retail'));
        @else
        // Price customization is available on retail AND wholesale
        document.querySelectorAll('.cart-custom-price').forEach(el => el.classList.remove('hidden'));
        @endif
        // The picker only lists what this sale type sells: retail keeps oil
        // fragrance from 50ml up, wholesale keeps oil fragrance only.
        filterProducts();
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
    const customerSearchBtn = document.getElementById('customerSearchBtn');
    let searchTimeout;
    let customerSearchSeq = 0;

    function setCustomerSearchBusy(busy) {
        if (!customerSearchBtn) return;
        customerSearchBtn.disabled = busy;
        customerSearchBtn.innerHTML = busy
            ? '<i class="fas fa-spinner fa-spin"></i>'
            : '<i class="fas fa-search"></i>';
    }

    // The one fetch behind both the typing and the search icon, so results are
    // identical whichever way the search was started.
    function runCustomerSearch(query, keepOpen, highlightId) {
        const q = String(query || '').trim();
        if (q.length < 2) { customerResults.classList.add('hidden'); return; }

        const seq = ++customerSearchSeq;
        setCustomerSearchBusy(true);

        fetch(`{{ route('api.customers.search') }}?q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(data => {
                if (seq !== customerSearchSeq) return;
                renderCustomerResults(Array.isArray(data) ? data : [], highlightId);
                customerResults.classList.toggle('hidden', !keepOpen);
            })
            .catch(() => {
                if (seq === customerSearchSeq && window.wcpToast) {
                    window.wcpToast.show('Could not search customers. Please try again.', 'error');
                }
            })
            .finally(() => {
                if (seq === customerSearchSeq) setCustomerSearchBusy(false);
            });
    }

    // Results are built with data attributes and one delegated handler, so a
    // name containing an apostrophe can never break the row's markup.
    function renderCustomerResults(data, highlightId) {
        if (!data.length) {
            customerResults.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No customers found. <button type="button" onclick="createCustomerFromSearch()" class="text-' + ACCENT + '-700 hover:underline">Create new</button></div>';
            return;
        }

        customerResults.innerHTML = data.map(c => {
            const id = Number(c.id);
            const name = c.name || 'Unnamed';
            const phone = c.phone || '';
            const active = highlightId != null && id === Number(highlightId);

            return `<button type="button" data-customer-id="${id}"
                        data-customer-name="${escapeHtml(name)}"
                        data-customer-phone="${escapeHtml(phone)}"
                        class="w-full px-3 py-2 text-sm text-left hover:bg-${ACCENT}-50 border-b last:border-0 ${active ? 'bg-' + ACCENT + '-50' : ''}">
                        <span class="font-medium">${escapeHtml(name)}</span>
                        ${phone ? `<span class="text-gray-500 ml-2">${escapeHtml(phone)}</span>` : ''}
                        ${active ? '<i class="fas fa-check text-emerald-600 ml-2"></i>' : ''}
                    </button>`;
        }).join('');
    }

    customerSearch.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) { customerResults.classList.add('hidden'); return; }
        searchTimeout = setTimeout(() => runCustomerSearch(q, true), 300);
    });

    customerSearch.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); searchCustomers(); }
    });

    if (customerSearchBtn) customerSearchBtn.addEventListener('click', searchCustomers);

    function searchCustomers() {
        const q = customerSearch.value.trim();
        if (q.length < 2) {
            if (window.wcpToast) window.wcpToast.show('Type at least two characters or digits to search.', 'warning');
            customerSearch.focus();
            return;
        }
        clearTimeout(searchTimeout);
        runCustomerSearch(q, true);
    }

    customerResults.addEventListener('click', function (e) {
        const row = e.target.closest('[data-customer-id]');
        if (!row) return;
        selectCustomer(Number(row.dataset.customerId), row.dataset.customerName || '', row.dataset.customerPhone || '');
    });

    function selectCustomer(id, name, phone) {
        document.getElementById('customer_id').value = id;
        document.getElementById('selectedCustomerName').textContent = name || 'Unnamed';
        document.getElementById('selectedCustomerPhone').textContent = phone || '';
        document.getElementById('selectedCustomer').classList.remove('hidden');
        document.getElementById('newCustomerForm').classList.add('hidden');
        document.getElementById('newCustomerBtn').classList.add('hidden');
        const newCustomerError = document.getElementById('newCustomerError');
        if (newCustomerError) newCustomerError.classList.add('hidden');
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
        const newCustomerError = document.getElementById('newCustomerError');
        if (newCustomerError) newCustomerError.classList.add('hidden');
    }

    function toggleNewCustomer() {
        const form = document.getElementById('newCustomerForm');
        form.classList.toggle('hidden');
        const newCustomerError = document.getElementById('newCustomerError');
        if (newCustomerError) newCustomerError.classList.add('hidden');
        if (!form.classList.contains('hidden')) {
            document.getElementById('selectedCustomer').classList.add('hidden');
            document.getElementById('customer_id').value = '';
            document.getElementById('newCustomerName').focus();
        }
    }

    function createCustomerFromSearch() {
        const q = customerSearch.value.trim();
        const nameEl = document.getElementById('newCustomerName');
        const phoneEl = document.getElementById('newCustomerPhone');

        document.getElementById('newCustomerForm').classList.remove('hidden');
        document.getElementById('newCustomerBtn').classList.remove('hidden');
        document.getElementById('selectedCustomer').classList.add('hidden');
        document.getElementById('customer_id').value = '';

        // A phone-like query fills the phone field instead of the name.
        if (q !== '' && /^[+\d][\d\s\-()]+$/.test(q)) {
            phoneEl.value = q;
            nameEl.value = '';
            nameEl.focus();
        } else {
            nameEl.value = q;
            phoneEl.value = '';
            phoneEl.focus();
        }

        customerResults.classList.add('hidden');
        customerSearch.value = '';
    }

    function showNewCustomerError(message) {
        const box = document.getElementById('newCustomerError');
        if (!box) return;
        box.textContent = message;
        box.classList.remove('hidden');
    }

    // Create the customer before the sale is submitted, then keep them
    // selected: the user never has to search for what they just saved.
    function saveNewCustomer() {
        const nameEl = document.getElementById('newCustomerName');
        const phoneEl = document.getElementById('newCustomerPhone');
        const button = document.getElementById('saveCustomerBtn');
        const name = nameEl.value.trim();
        const phone = phoneEl.value.trim();

        const errorBox = document.getElementById('newCustomerError');
        if (errorBox) errorBox.classList.add('hidden');

        if (name === '') { showNewCustomerError('Enter the customer name.'); nameEl.focus(); return; }
        if (phone === '') { showNewCustomerError('Enter the customer phone number.'); phoneEl.focus(); return; }

        const original = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';

        fetch(`{{ route('api.customers.store') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ name: name, phone: phone })
        })
            .then(async response => {
                let payload = {};
                try { payload = await response.json(); } catch (e) { payload = {}; }

                if (!response.ok) {
                    const first = payload.errors ? Object.values(payload.errors)[0] : null;
                    const message = Array.isArray(first) ? first[0] : (first || payload.message || 'Could not save the customer. Please try again.');
                    throw new Error(message);
                }

                return payload;
            })
            .then(payload => {
                const customer = payload.customer || {};
                if (!customer.id) throw new Error('Could not save the customer. Please try again.');

                const savedName = customer.name || name;
                const savedPhone = customer.phone || phone;

                // A newly added customer needs no notice; the selected card above
                // already shows them. Only a duplicate phone warns that the
                // existing record was used.
                if (window.wcpToast && payload.duplicate) {
                    window.wcpToast.show(
                        payload.message || 'This phone number already exists. The existing customer has been retrieved.',
                        'warning'
                    );
                }

                // Close the form, select the saved customer for the sale, and
                // refresh the suggestion list with them visible in it.
                selectCustomer(customer.id, savedName, savedPhone);
                customerSearch.value = savedName;
                customerResults.classList.remove('hidden');
                runCustomerSearch(savedName, true, customer.id);
            })
            .catch(error => {
                showNewCustomerError(error.message || 'Could not save the customer. Please try again.');
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = original;
            });
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#customerSearch') && !e.target.closest('#customerResults') && !e.target.closest('#customerSearchBtn')) {
            customerResults.classList.add('hidden');
        }
    });

    // ===================== PRODUCT PICKER (searchable checkboxes) =====================
    const productSearch = document.getElementById('productSearch');

    // Retail bottles oil fragrance from 50ml up, so the 6ml, 12ml and 30ml
    // rows are not sold retail. Wholesale buys oil fragrance in every size but
    // never brand perfumes. A row the sale type does not sell is hidden AND
    // taken out of the cart — hiding it alone would still post it.
    const RETAIL_MIN_OIL_ML = 50;

    function rowSellable(label) {
        const type = document.getElementById('sale_type').value;
        const category = label.dataset.category || '';
        const volume = Number(label.dataset.volume || 0);
        if (type === 'wholesale') return category === 'Oil Fragrance';
        if (category === 'Oil Fragrance' && volume > 0) return volume >= RETAIL_MIN_OIL_ML;
        return true;
    }

    function filterProducts() {
        const q = (productSearch.value || '').trim().toLowerCase();
        let visible = 0;
        document.querySelectorAll('.product-pick').forEach(label => {
            const name = (label.querySelector('.product-check').dataset.name || '').toLowerCase();
            const sellable = rowSellable(label);
            const show = sellable && (!q || name.includes(q));
            label.style.display = show ? '' : 'none';
            if (show) visible++;
            if (!sellable) {
                const box = label.querySelector('.product-check');
                if (box && box.checked) removeFromCartRow(box.dataset.rowKey);
            }
        });

        const noMatch = document.getElementById('product-no-match');
        if (visible === 0) {
            noMatch.innerHTML = '<i class="fas fa-info-circle mr-1"></i> '
                + (q ? 'No products match your search.' : 'Nothing in stock sells as this sale type.');
        }
        noMatch.classList.toggle('hidden', visible > 0);

        const rule = document.getElementById('product-sale-rule');
        if (rule) {
            rule.innerHTML = document.getElementById('sale_type').value === 'wholesale'
                ? '<i class="fas fa-info-circle mr-1"></i> Wholesale: oil fragrance only, any size. Brand perfumes are not sold wholesale.'
                : '<i class="fas fa-info-circle mr-1"></i> Retail: oil fragrance from 50ml up. 6ml, 12ml and 30ml are wholesale only.';
        }
    }
    productSearch.addEventListener('input', filterProducts);

    document.querySelectorAll('.product-check').forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked) addToCart(this);
            else removeFromCartRow(this.dataset.rowKey);
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
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;',"'": '&#39;' }[c]));
    }

    function cartCount() { return document.querySelectorAll('.cart-row').length; }

    // Unique key per cart line: a variety line is product|volume|variant,
    // a plain product is product|| — so the same product can sit in the
    // cart once per bottling.
    function rowKey(productId, volume, variant) {
        return productId + '|' + (volume || '') + '|' + (variant || '');
    }

    // One checkbox = one saleable line. A variety checkbox already knows
    // its bottling, price and available count, so the cart line inherits
    // all three and posts volume/variant straight through.
    function addToCart(cb) {
        const cart = document.getElementById('cart-rows');
        const volume = String(cb.dataset.volume || '');
        const variant = String(cb.dataset.variant || '');
        const isVariety = volume !== '' && variant !== '';
        const key = cb.dataset.rowKey || rowKey(cb.value, volume, variant);
        if (cart.querySelector('.cart-row[data-row-key="' + key + '"]')) return;

        const name = cb.dataset.name;
        const available = parseInt(cb.dataset.stock || 0);
        const price = parseFloat(cb.dataset.price || 0);

        const tr = document.createElement('tr');
        tr.className = 'cart-row';
        tr.dataset.productId = cb.value;
        tr.dataset.rowKey = key;
        tr.dataset.stock = available;
        tr.dataset.unitPrice = price;
        tr.innerHTML =
            '<td class="px-3 py-2">' +
                '<span class="font-medium text-sm block">' + escapeHtml(name) + '</span>' +
                '<span class="text-xs text-gray-500">' + available + ' in stock</span>' +
                (isVariety
                    ? '<input type="hidden" class="cart-variety-volume" value="' + escapeHtml(volume) + '">' +
                      '<input type="hidden" class="cart-variety-variant" value="' + escapeHtml(variant) + '">'
                    : '') +
                '<input type="hidden" class="cart-product-hidden">' +
                '<input type="hidden" class="cart-qty-hidden" value="1">' +
            '</td>' +
            '<td class="px-3 py-2">' +
                '<span class="cart-unit-price text-sm block"></span>' +
                @if (!empty($hasDiscount))
                '<input type="number" step="0.01" min="0" class="cart-discount-price hidden mt-1 w-28 px-2 py-1 border border-dashed rounded text-xs" placeholder="Custom price">' +
                @endif
                @if (empty($hasDiscount))
                '<input type="number" step="0.01" min="0" class="cart-custom-price mt-1 w-28 px-2 py-1 border rounded text-xs" placeholder="Custom price">' +
                @else
                '<input type="number" step="0.01" min="0" class="cart-custom-price hidden mt-1 w-28 px-2 py-1 border rounded text-xs" placeholder="Custom price">' +
                @endif
            '</td>' +
            '<td class="px-3 py-2 text-center whitespace-nowrap">' +
                '<div class="inline-flex items-center border rounded-lg">' +
                    '<button type="button" data-cart-step="-1" class="cart-dec w-7 h-7 text-gray-600 hover:bg-gray-100 rounded-l-lg">&minus;</button>' +
                    '<span class="cart-qty w-8 text-center text-sm font-medium">1</span>' +
                    '<button type="button" data-cart-step="1" class="cart-inc w-7 h-7 text-gray-600 hover:bg-gray-100 rounded-r-lg">+</button>' +
                '</div>' +
            '</td>' +
            '<td class="px-3 py-2 text-right cart-line-total text-sm font-medium">' + formatMoney(0) + '</td>' +
            '<td class="px-2 py-2 text-center">' +
                '<button type="button" class="text-red-500 hover:text-red-700" title="Remove" onclick="removeFromCartRow(\'' + key + '\')"><i class="fas fa-times"></i></button>' +
            '</td>';

        cart.appendChild(tr);
        document.getElementById('cart-empty').classList.add('hidden');

        // Show the line's own default price so it is obvious what a variety
        // sells for before anything is overridden.
        if (price > 0) {
            const priceInput = tr.querySelector('.cart-custom-price');
            if (priceInput) {
                priceInput.value = '';
                priceInput.placeholder = 'Default ' + Number(price).toLocaleString();
            }
        }

        const custom = tr.querySelector('.cart-custom-price');
        custom.addEventListener('input', calculateTotal);
        @if (!empty($hasDiscount))
        custom.classList.toggle('hidden', document.getElementById('sale_type').value !== 'wholesale');
        @endif
        @if (!empty($hasDiscount))
        const discount = tr.querySelector('.cart-discount-price');
        discount.addEventListener('input', calculateTotal);
        discount.classList.toggle('hidden', document.getElementById('sale_type').value !== 'retail');
        @endif

        reindex();
        calculateTotal();
    }

    // Remove a cart line by its row key and untick the product line it came
    // from, so the picker and the cart never disagree.
    function removeFromCartRow(key) {
        const row = document.getElementById('cart-rows').querySelector('.cart-row[data-row-key="' + key + '"]');
        if (row) row.remove();
        const cb = document.querySelector('.product-check[data-row-key="' + key + '"]');
        if (cb) cb.checked = false;
        if (cartCount() === 0) document.getElementById('cart-empty').classList.remove('hidden');
        reindex();
        calculateTotal();
    }

    function changeQty(btn, delta) {
        const row = btn.closest('.cart-row');
        if (!row) return;

        const qtyEl = row.querySelector('.cart-qty');
        const qtyInput = row.querySelector('.cart-qty-hidden');

        // The hidden field is what posts AND what the totals are computed from,
        // so it is the source of truth here too: the number on screen can never
        // drift from the quantity that is actually sent.
        let q = parseInt(qtyInput && qtyInput.value ? qtyInput.value : qtyEl.textContent, 10);
        if (!Number.isFinite(q) || q < 1) q = 1;

        let next = q + delta;
        if (next < 1) next = 1;

        // Never exceed what's actually in stock for this line's bottling. When
        // the line is already at its stock, say so — a cap that refuses without
        // a word reads as a dead + button, which is exactly how this page was
        // reported.
        const max = parseInt(row.dataset.stock || 0, 10);
        if (delta > 0 && max > 0 && next > max) {
            next = max;
            if (window.wcpToast && typeof window.wcpToast.show === 'function') {
                window.wcpToast.show('Only ' + max + ' in stock for this line.', 'warning');
            }
        }

        qtyEl.textContent = next;
        if (qtyInput) qtyInput.value = next;
        calculateTotal();
    }

    // The steppers are wired once, by delegation, on the cart body: every row is
    // built by addToCart after this script has run, and a delegated listener
    // needs no per-row binding and no global function name to resolve.
    (function () {
        const cartBody = document.getElementById('cart-rows');
        if (!cartBody) return;
        cartBody.addEventListener('click', function (event) {
            const button = event.target.closest('[data-cart-step]');
            if (!button) return;
            changeQty(button, parseInt(button.dataset.cartStep, 10) || 0);
        });
    })();

    function reindex() {
        document.querySelectorAll('.cart-row').forEach((row, i) => {
            row.querySelector('.cart-product-hidden').value = row.dataset.productId;
            row.querySelector('.cart-product-hidden').name = 'items[' + i + '][product_id]';
            row.querySelector('.cart-qty-hidden').name = 'items[' + i + '][quantity]';
            const vol = row.querySelector('.cart-variety-volume');
            const varSel = row.querySelector('.cart-variety-variant');
            if (vol) vol.name = 'items[' + i + '][volume]';
            if (varSel) varSel.name = 'items[' + i + '][variant]';
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
            // Each line carries its own unit price. A product id is no longer
            // enough to find it: one product can be in the cart once per
            // bottling, each at its own price.
            const price = parseFloat(row.dataset.unitPrice || 0);
            const qty = parseInt(row.querySelector('.cart-qty-hidden').value || 0);
            const custom = row.querySelector('.cart-custom-price');
            let unit = price;
            @if (!empty($hasDiscount))
            if (isWholesale && custom && !custom.classList.contains('hidden') && custom.value) unit = parseFloat(custom.value) || price;
            @else
            // Custom price applies on both retail and wholesale
            if (custom && custom.value) unit = parseFloat(custom.value) || price;
            @endif
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
        const total = computeSaleTotal();
        document.getElementById('grand-total').textContent = formatMoney(total);
        // The payment step repeats the amount due, so the figure follows the
        // cart even while the products step is off screen.
        const paymentTotal = document.getElementById('payment-grand-total');
        if (paymentTotal) paymentTotal.textContent = formatMoney(total);
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

    // ===================== VARIETY VALIDATION ON SUBMIT =====================
    (function () {
        const form = document.getElementById('saleForm');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            // The Save as Draft button lives on the products step now, so it
            // must be allowed to submit before the payment step is reached.
            const isDraft = e.submitter && e.submitter.hasAttribute('data-save-draft');
            if (isDraft) return;

            // Enter in an early field (or any programmatic submit) must not
            // post a sale before the payment step has been reached.
            if (saleStep !== 4) {
                e.preventDefault();
                return;
            }
            const missing = [];
            document.querySelectorAll('.cart-row').forEach(row => {
                // A bottled line is one that carries a variety at all; the
                // picker fills these in, so this only catches tampering.
                const vol = row.querySelector('.cart-variety-volume');
                if (!vol) return;
                const varSel = row.querySelector('.cart-variety-variant');
                if (!vol.value || !varSel || !varSel.value) {
                    const nameEl = row.querySelector('.font-medium');
                    missing.push(nameEl ? nameEl.textContent : 'a product');
                }
            });
            if (missing.length) {
                e.preventDefault();
                alert('Select the bottle volume and variety for: ' + missing.join(', ') + '.');
            }
        });
    })();

    // ===================== STEP WIZARD =====================
    // The sale is answered in one fixed order: sale type, customer, products,
    // payment. Only the current step is on screen, so nothing can be skipped
    // or filled in out of sequence, and the server sees exactly the same
    // field names it always did — the steps are presentation only.
    const SALE_LAST_STEP = 4;
    let saleStep = 1;

    function saleStepPanels() {
        return Array.from(document.querySelectorAll('.sale-step'));
    }

    // A wholesale sale may be empty bottles only, so a filled bottle line
    // counts as a sale line exactly like a product in the cart does.
    function bottleLineReady(row) {
        const volume = row.querySelector('.bottle-volume-select');
        const qty = row.querySelector('.bottle-qty-input');
        const price = row.querySelector('.bottle-price-input');
        return !!(volume && volume.value && qty && (parseInt(qty.value, 10) || 0) > 0 && price && price.value !== '');
    }

    function saleHasLines() {
        if (document.getElementById('cart-rows').querySelectorAll('.cart-row').length > 0) return true;
        return Array.from(document.querySelectorAll('.bottle-item-row')).some(bottleLineReady);
    }

    function validateSaleStep(step) {
        if (step === 3 && !saleHasLines()) {
            if (window.wcpToast && typeof window.wcpToast.show === 'function') {
                window.wcpToast.show('Add at least one product or empty bottle to continue.', 'warning');
            } else {
                alert('Add at least one product or empty bottle to continue.');
            }
            return false;
        }
        return true;
    }

    function paintSaleSteps() {
        document.querySelectorAll('[data-step-go]').forEach(btn => {
            const step = Number(btn.dataset.stepGo);
            const active = step === saleStep;
            const done = step < saleStep;
            const dot = btn.querySelector('.sale-step-dot');
            const label = btn.querySelector('.sale-step-label');
            if (dot) {
                dot.className = 'sale-step-dot flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-all ' +
                    (active ? 'bg-' + ACCENT + '-600 text-white shadow'
                        : done ? 'bg-' + ACCENT + '-100 text-' + ACCENT + '-700'
                        : 'bg-gray-100 text-gray-400');
            }
            if (label) {
                label.className = 'sale-step-label hidden sm:block truncate text-xs font-semibold ' +
                    (active ? 'text-' + ACCENT + '-700' : done ? 'text-gray-600' : 'text-gray-400');
            }
            btn.setAttribute('aria-current', active ? 'step' : 'false');
            btn.classList.toggle('cursor-default', step > saleStep);
        });
    }

    function goToStep(step, initial) {
        step = Math.max(1, Math.min(SALE_LAST_STEP, Number(step) || 1));
        // Forward moves are one step at a time and only after the current step
        // is answered; going back is always free.
        if (!initial && step > saleStep) {
            if (step > saleStep + 1) return;
            if (!validateSaleStep(saleStep)) return;
        }
        saleStep = step;
        saleStepPanels().forEach(panel => {
            panel.classList.toggle('hidden', Number(panel.dataset.step) !== saleStep);
        });
        paintSaleSteps();
        if (!initial) window.scrollTo({ top: 0, behavior: 'smooth' });
        if (saleStep === 4) {
            calculateTotal();
            validatePayments();
        }
    }

    // Small screens have no room for both actions, so the skip is explicit:
    // it clears the customer fields and books the sale as walk-in.
    function skipCustomer() {
        clearCustomer();
        document.getElementById('newCustomerForm').classList.add('hidden');
        document.getElementById('newCustomerName').value = '';
        document.getElementById('newCustomerPhone').value = '';
        goToStep(3);
    }

    // ===================== DRAFT RESUME =====================
    // A parked sale reopens with the customer, cart, bottles and payments it
    // was saved with. The draft is the payload the server stored.
    function hydrateDraft(draft) {
        if (!draft || typeof draft !== 'object') return;

        // Sale type first — switching to retail resets the bottle lines.
        setSaleType(draft.sale_type === 'wholesale' ? 'wholesale' : 'retail');

        if (draft.customer_id) {
            selectCustomer(draft.customer_id, draft.customer_name || 'Customer', draft.customer_phone || '');
        } else if (draft.customer_name || draft.customer_phone) {
            document.getElementById('newCustomerForm').classList.remove('hidden');
            document.getElementById('newCustomerName').value = draft.customer_name || '';
            document.getElementById('newCustomerPhone').value = draft.customer_phone || '';
            document.getElementById('selectedCustomer').classList.add('hidden');
            document.getElementById('customer_id').value = '';
        }

        (draft.items || []).forEach(function (item) {
            const volume = item.volume ? String(item.volume) : '';
            const variant = item.variant || '';
            const cb = document.querySelector('.product-check[data-row-key="' + item.product_id + '|' + volume + '|' + variant + '"]');
            if (!cb) return;

            cb.checked = true;
            addToCart(cb);

            const row = document.querySelector('.cart-row[data-row-key="' + cb.dataset.rowKey + '"]');
            if (!row) return;

            const qty = parseInt(item.quantity || 1, 10) || 1;
            row.querySelector('.cart-qty').textContent = qty;
            row.querySelector('.cart-qty-hidden').value = qty;

            const price = row.querySelector('.cart-custom-price');
            if (price && item.custom_price !== null && item.custom_price !== undefined && item.custom_price !== '') {
                price.value = item.custom_price;
            }

            // A manual retail discount (stock manager forms) is part of the
            // parked sale too, or the resumed total would change.
            const discount = row.querySelector('.cart-discount-price');
            if (discount && item.discount_price !== null && item.discount_price !== undefined && item.discount_price !== '') {
                discount.value = item.discount_price;
            }
        });

        (draft.empty_bottles || []).forEach(function (bottle, i) {
            const container = document.getElementById('bottle-items-container');
            if (i > 0) addBottleRow();
            const row = container.querySelectorAll('.bottle-item-row')[i];
            if (!row) return;

            row.querySelector('.bottle-volume-select').value = String(bottle.volume || '');
            refreshBottleVariant(row);

            const variant = row.querySelector('.bottle-variant-select');
            if (variant && bottle.variant) variant.value = bottle.variant;

            row.querySelector('.bottle-qty-input').value = bottle.quantity || 1;
            row.querySelector('.bottle-price-input').value = (bottle.price !== null && bottle.price !== undefined) ? bottle.price : '';
        });

        // Payments last: the mode decides which of the rows below are posted.
        const mode = draft.payment_mode === 'multi' ? 'multi' : 'single';
        const radio = document.querySelector('input[name="payment_mode"][value="' + mode + '"]');
        if (radio) {
            radio.checked = true;
            togglePaymentMode(mode);
        }

        const payments = draft.payments || [];
        if (mode === 'single') {
            const method = document.querySelector('#singlePayment select[name="payments[0][method]"]');
            if (method && payments[0]) method.value = payments[0].method || 'cash';
        } else {
            payments.forEach(function (payment, i) { if (i > 0) addPaymentRow(); });

            const rows = document.querySelectorAll('#payment-rows .payment-row');
            payments.forEach(function (payment, i) {
                const row = rows[i];
                if (!row) return;
                const method = row.querySelector('select');
                const amount = row.querySelector('input');
                if (method) method.value = payment.method || 'cash';
                if (amount && payment.amount !== null && payment.amount !== undefined) amount.value = payment.amount;
            });
        }

        calculateTotal();
    }

    // ===================== INIT =====================
    bindBottleEvents();
    document.querySelectorAll('.bottle-item-row').forEach(row => refreshBottleVariant(row));
    togglePaymentMode('single');
    setSaleType(document.getElementById('sale_type').value);
    goToStep(1, true);

    // Open a resumed draft straight on the payment step, with everything
    // already filled in.
    if (window.SALE_DRAFT) {
        hydrateDraft(window.SALE_DRAFT);
        goToStep(4, true);
    }
</script>