@extends('stock-manager.layouts.app')
@section('title', 'QR Code Generator')
@section('header', 'QR Code Generator')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-qrcode text-emerald-500 text-2xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-lg mb-2">Website QR Code</h3>
        <p class="text-sm text-gray-500 mb-6">Scan this QR code to visit the World Choice Perfumes website</p>

        <div class="bg-gray-50 rounded-xl p-6 inline-block mb-6">
            <canvas id="qrCanvas"></canvas>
        </div>

        <p class="text-xs text-gray-400 mb-6 break-all">{{ $url }}</p>

        <div class="flex justify-center gap-3">
            <button onclick="downloadQR()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                <i class="fas fa-download mr-1"></i> Download QR Code
            </button>
            <button onclick="printQR()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                <i class="fas fa-print mr-1"></i> Print
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById("qrCanvas"), {
        text: "{{ $url }}",
        width: 256,
        height: 256,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });

    function downloadQR() {
        const canvas = document.querySelector('#qrCanvas canvas') || document.querySelector('#qrCanvas img');
        if (canvas.tagName === 'CANVAS') {
            const link = document.createElement('a');
            link.download = 'world-choice-perfumes-qr.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } else {
            const link = document.createElement('a');
            link.download = 'world-choice-perfumes-qr.png';
            link.href = canvas.src;
            link.click();
        }
    }

    function printQR() {
        const img = document.querySelector('#qrCanvas img') || document.querySelector('#qrCanvas canvas');
        const src = img.tagName === 'IMG' ? img.src : img.toDataURL('image/png');
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head><title>QR Code - World Choice Perfumes</title></head>
            <body style="text-align:center; padding:40px;">
                <h2>World Choice Perfumes</h2>
                <p>Scan to visit our website</p>
                <img src="${src}" width="256" height="256">
                <p style="margin-top:20px; font-size:12px; color:#666;">{{ $url }}</p>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
</script>
@endpush
@endsection
