@extends('layouts.app')
@section('title', 'QR Code Generator')
@section('header', 'QR Code Generator')

@section('content')
<div class="max-w-3xl mx-auto">
    {{-- Two kinds of code come out of this page: one for the shop as a whole
         (the same code on every poster and receipt) and one per perfume, so a
         shelf-talker or a bottle sticker can open the order page directly.
         Tabs rather than two stacked cards, because the second one is only
         useful once a product is picked. --}}
    <div class="flex flex-wrap gap-2 mb-6" role="tablist" aria-label="What the QR code should open">
        <button type="button" role="tab" id="qrTab-general" aria-controls="qrPanel-general" aria-selected="true" tabindex="0" data-qr-tab="general"
                class="qr-tab inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium border border-gray-200 bg-gray-100 text-gray-600 transition">
            <i class="fas fa-globe" aria-hidden="true"></i> Create General QR Code
        </button>
        <button type="button" role="tab" id="qrTab-product" aria-controls="qrPanel-product" aria-selected="false" tabindex="-1" data-qr-tab="product"
                class="qr-tab inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium border border-gray-200 bg-gray-100 text-gray-600 transition">
            <i class="fas fa-spray-can-sparkles" aria-hidden="true"></i> Create Product QR Code
        </button>
    </div>

    {{-- ============================ General ============================ --}}
    <div data-qr-panel="general" role="tabpanel" aria-labelledby="qrTab-general" tabindex="0"
         class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-qrcode text-purple-500 text-2xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-lg mb-2">Website QR Code</h3>
        <p class="text-sm text-gray-500 mb-6">Scan this QR code to visit the World Choice Perfume website</p>

        <div class="bg-gray-50 rounded-xl p-6 inline-block mb-6">
            <div id="qrCanvas"></div>
        </div>

        <p class="text-xs text-gray-400 mb-6 break-all">{{ $url }}</p>

        <div class="flex justify-center gap-3">
            <button onclick="downloadGeneralQR()" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                <i class="fas fa-download mr-1"></i> Download QR Code
            </button>
            <button onclick="printGeneralQR()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                <i class="fas fa-print mr-1"></i> Print
            </button>
        </div>
    </div>

    {{-- ============================ Product ============================ --}}
    <div data-qr-panel="product" role="tabpanel" aria-labelledby="qrTab-product" tabindex="0" class="hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <div class="text-center mb-6">
                <h3 class="font-semibold text-gray-800 text-lg mb-2">Product QR Code</h3>
                <p class="text-sm text-gray-500">Choose a perfume and the code opens its order page — the customer lands on the product, not the shop.</p>
            </div>

            <div class="relative mb-4">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                <input type="search" id="productSearch" autocomplete="off" placeholder="Search by name, brand or category…"
                       aria-controls="productList" aria-label="Filter products"
                       class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500/30 outline-none transition">
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div id="productList" class="max-h-72 overflow-y-auto" role="listbox" aria-label="Active products">
                    @forelse($products as $product)
                        <button type="button" data-product role="option" aria-selected="false"
                                data-url="{{ $product->url }}" data-name="{{ $product->name }}"
                                data-search="{{ \Illuminate\Support\Str::lower($product->name.' '.$product->brand.' '.$product->category) }}"
                                class="product-row w-full flex items-center justify-between gap-3 px-4 py-3 text-left text-sm bg-white border-b border-gray-200 last:border-b-0 hover:bg-gray-100 transition">
                            <span class="min-w-0">
                                <span class="block font-medium text-gray-800 truncate">{{ $product->name }}</span>
                                <span class="block text-xs text-gray-500 truncate">
                                    {{ $product->brand ?: '—' }}@if($product->category) · {{ $product->category }}@endif
                                </span>
                            </span>
                            <i class="fas fa-qrcode product-row-icon text-gray-300" aria-hidden="true"></i>
                        </button>
                    @empty
                        <p class="px-4 py-6 text-sm text-gray-500 text-center">No active products to link to yet.</p>
                    @endforelse
                </div>
            </div>

            <p id="productEmpty" class="hidden px-4 py-4 text-sm text-gray-500 text-center">No product matches that search.</p>

            {{-- Filled in when a product is chosen; hidden until then so the
                 page never shows an empty white square. --}}
            <div id="productResult" class="hidden mt-6 pt-6 border-t border-gray-200 text-center">
                <div class="bg-gray-50 rounded-xl p-6 inline-block mb-4">
                    <div id="productQrBox"></div>
                </div>

                <p id="productQrName" class="font-semibold text-gray-800 mb-1"></p>
                <p id="productQrUrl" class="text-xs text-gray-400 mb-6 break-all"></p>

                <div class="flex justify-center gap-3">
                    <button type="button" onclick="downloadProductQR()" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                        <i class="fas fa-download mr-1"></i> Download QR Code
                    </button>
                    <button type="button" onclick="printProductQR()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                        <i class="fas fa-print mr-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* The tab that is selected wears the Graphic Designer purple; the other
       stays a plain chip. Written as CSS rather than a swapped Tailwind class
       so the state is decided by aria-selected alone — one source of truth for
       the screen reader and the screen. */
    .qr-tab[aria-selected="true"] {
        background-color: #9333EA;
        border-color: #9333EA;
        color: #FFFFFF;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    /* ------------------------------------------------------------------ *
     * QR helpers — qrcodejs paints a <canvas> (an <img> on some browsers),
     * so every download/print reads whichever it produced.
     * ------------------------------------------------------------------ */
    function qrNode(box) {
        return box ? (box.querySelector('canvas') || box.querySelector('img')) : null;
    }

    function drawQr(box, text) {
        if (!box || !window.QRCode) return false;
        box.innerHTML = '';
        // Reached through window, matching the guard above: if qrcodejs did not
        // load, this returns false instead of throwing mid-gesture.
        new window.QRCode(box, {
            text: text,
            width: 256,
            height: 256,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: window.QRCode.CorrectLevel.H
        });
        return true;
    }

    function downloadQr(box, filename) {
        const node = qrNode(box);
        if (!node) return;
        const link = document.createElement('a');
        link.download = filename;
        link.href = node.tagName === 'CANVAS' ? node.toDataURL('image/png') : node.src;
        link.click();
    }

    function printQr(box, title, caption, footnote) {
        const node = qrNode(box);
        if (!node) return;
        const src = node.tagName === 'CANVAS' ? node.toDataURL('image/png') : node.src;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head><title>${title} - World Choice Perfume</title></head>
            <body style="text-align:center; padding:40px; font-family: sans-serif;">
                <h2>World Choice Perfume</h2>
                <p>${caption}</p>
                <img src="${src}" width="256" height="256">
                <p style="margin-top:20px; font-size:12px; color:#666;">${footnote || ''}</p>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    /* ------------------------------------------------------------------ *
     * General tab — the shop's own address, drawn straight away because it
     * needs nothing selected.
     * ------------------------------------------------------------------ */
    const generalBox = document.getElementById('qrCanvas');
    const generalUrl = @json($url);
    drawQr(generalBox, generalUrl);

    function downloadGeneralQR() {
        downloadQr(generalBox, 'world-choice-perfumes-qr.png');
    }

    function printGeneralQR() {
        printQr(generalBox, 'QR Code', 'Scan to visit our website', generalUrl);
    }

    /* ------------------------------------------------------------------ *
     * Product tab — the picker. The whole catalogue is rendered once and
     * filtered in the browser, so typing narrows the list without a request
     * per keystroke.
     * ------------------------------------------------------------------ */
    const productRows = Array.from(document.querySelectorAll('[data-product]'));
    const productSearch = document.getElementById('productSearch');
    const productList = document.getElementById('productList');
    const productEmpty = document.getElementById('productEmpty');
    const productResult = document.getElementById('productResult');
    const productQrBox = document.getElementById('productQrBox');
    const productQrName = document.getElementById('productQrName');
    const productQrUrl = document.getElementById('productQrUrl');
    let selectedProduct = null;

    function filterProducts() {
        const term = (productSearch.value || '').trim().toLowerCase();
        let shown = 0;

        productRows.forEach(function (row) {
            const match = term === '' || row.getAttribute('data-search').indexOf(term) !== -1;
            row.classList.toggle('hidden', !match);
            if (match) shown++;
        });

        productList.classList.toggle('hidden', shown === 0);
        productEmpty.classList.toggle('hidden', shown > 0);
    }

    function chooseProduct(row) {
        productRows.forEach(function (other) {
            const on = other === row;
            other.setAttribute('aria-selected', on ? 'true' : 'false');
            other.classList.toggle('bg-purple-50', on);
            other.querySelector('.product-row-icon').className =
                'fas product-row-icon ' + (on ? 'fa-check-circle text-purple-600' : 'fa-qrcode text-gray-300');
        });

        selectedProduct = {
            name: row.getAttribute('data-name'),
            url: row.getAttribute('data-url')
        };

        productQrName.textContent = selectedProduct.name;
        productQrUrl.textContent = selectedProduct.url;
        productResult.classList.remove('hidden');
        drawQr(productQrBox, selectedProduct.url);
        productResult.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    if (productSearch) {
        productSearch.addEventListener('input', filterProducts);
    }

    productRows.forEach(function (row) {
        row.addEventListener('click', function () { chooseProduct(row); });
    });

    function productFilename() {
        const slug = selectedProduct
            ? selectedProduct.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
            : 'product';
        return 'world-choice-' + slug + '-qr.png';
    }

    function downloadProductQR() {
        if (!selectedProduct) return;
        downloadQr(productQrBox, productFilename());
    }

    function printProductQR() {
        if (!selectedProduct) return;
        printQr(productQrBox, selectedProduct.name, 'Scan to order ' + selectedProduct.name, selectedProduct.url);
    }

    /* ------------------------------------------------------------------ *
     * Tabs — aria-selected drives both the panel and the styling.
     * ------------------------------------------------------------------ */
    const qrTabs = Array.from(document.querySelectorAll('[data-qr-tab]'));
    const qrPanels = Array.from(document.querySelectorAll('[data-qr-panel]'));

    function showTab(name) {
        qrTabs.forEach(function (tab) {
            const on = tab.getAttribute('data-qr-tab') === name;
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.setAttribute('tabindex', on ? '0' : '-1');
        });
        qrPanels.forEach(function (panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-qr-panel') !== name);
        });
    }

    qrTabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () { showTab(tab.getAttribute('data-qr-tab')); });
        tab.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
            event.preventDefault();
            const next = (index + (event.key === 'ArrowRight' ? 1 : qrTabs.length - 1)) % qrTabs.length;
            showTab(qrTabs[next].getAttribute('data-qr-tab'));
            qrTabs[next].focus();
        });
    });
</script>
@endpush
@endsection
