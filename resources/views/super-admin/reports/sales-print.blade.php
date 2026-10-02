<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Print Sales Report' }}</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; background: #ffffff; }
        iframe { display: block; width: 100%; height: 100vh; border: 0; }
        .print-fallback {
            position: fixed;
            left: 50%;
            bottom: 16px;
            transform: translateX(-50%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 12px;
            color: #6b7280;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .print-fallback a { color: #b45309; font-weight: 600; }
    </style>
</head>
<body>
    {{-- The generated PDF is rendered here and the print dialog is opened for
         it as soon as it has loaded. --}}
    <iframe id="report-pdf" src="{{ $pdfUrl }}" title="Sales report"></iframe>

    <p class="print-fallback">
        Report ready to print.
        <a href="{{ $pdfUrl }}" target="_blank" rel="noopener">Open the PDF</a> if the dialog did not appear.
    </p>

    <script>
        (function () {
            var frame = document.getElementById('report-pdf');
            var printed = false;

            function openPrintDialog() {
                if (printed || !frame) return;
                printed = true;

                try {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } catch (e) {
                    // Some browsers block scripting the embedded PDF viewer —
                    // fall back to opening the PDF so it can be printed there.
                    window.open(@json($pdfUrl), '_blank');
                }
            }

            // Prefer the frame's load event, but some browsers never fire it for
            // PDF plugins, so also try once after a short delay.
            frame.addEventListener('load', function () {
                setTimeout(openPrintDialog, 400);
            });
            setTimeout(openPrintDialog, 2500);
        })();
    </script>
</body>
</html>
