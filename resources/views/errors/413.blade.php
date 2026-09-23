<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Too Large — World Choice Perfume</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-lg max-w-md w-full p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center">
                <i class="fas fa-file-image text-2xl text-amber-600"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-800 mb-2">File Too Large</h1>
            <p class="text-sm text-gray-600 mb-1">{{ $message ?? 'The uploaded file is too large. Images must be 50MB or less — please choose a smaller file and try again.' }}</p>
            <p class="text-xs text-gray-400 mb-6">Nothing was saved. Your other form entries were not lost — go back and re-select a smaller image.</p>
            <div class="flex items-center justify-center gap-3">
                @if(!empty($previousUrl))
                    <a href="{{ $previousUrl }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white text-sm font-medium hover:opacity-90 transition" style="background-color: #F89A1E;">
                        <i class="fas fa-arrow-left"></i> Go back
                    </a>
                @endif
                <button onclick="history.length > 1 ? history.back() : window.location.href = '/'"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium transition">
                    <i class="fas fa-rotate-left"></i> Try again
                </button>
            </div>
        </div>
    </div>
</body>
</html>
