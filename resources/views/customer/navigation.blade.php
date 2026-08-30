<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Twende Dukani — {{ $branch->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        #map { width: 100%; height: 100%; }

        .customer-marker {
            background: #3b82f6;
            width: 20px; height: 20px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 10px rgba(59,130,246,0.6);
            animation: customerPulse 2s infinite;
        }
        @keyframes customerPulse {
            0% { box-shadow: 0 0 0 0 rgba(59,130,246,0.7); }
            70% { box-shadow: 0 0 0 12px rgba(59,130,246,0); }
            100% { box-shadow: 0 0 0 0 rgba(59,130,246,0); }
        }

        .branch-marker {
            background: #dc2626;
            width: 24px; height: 24px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 10px rgba(220,38,38,0.6);
            display: flex; align-items: center; justify-content: center;
        }
        .branch-marker i { color: white; font-size: 10px; }

        .nav-line {
            stroke: #f59e0b;
            stroke-width: 4;
            stroke-dasharray: 10, 8;
            animation: dash 30s linear infinite;
        }
        @keyframes dash {
            to { stroke-dashoffset: -1000; }
        }

        .leaflet-control-zoom { border: none !important; }
        .leaflet-control-zoom a {
            background: #1f2937 !important;
            color: white !important;
            border: none !important;
            width: 36px !important;
            height: 36px !important;
            line-height: 36px !important;
            font-size: 16px !important;
        }
        .leaflet-control-zoom a:hover { background: #374151 !important; }
    </style>
</head>
<body class="bg-gray-900">
    <!-- Top Bar -->
    <div class="fixed top-0 left-0 right-0 z-[1000] bg-gradient-to-r from-amber-900 to-amber-800 text-white px-4 py-3 shadow-lg">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-8 h-8 rounded-full object-cover">
                <div>
                    <h1 class="font-bold text-sm">Twende Dukani</h1>
                    <p class="text-[10px] text-amber-200">{{ $branch->name }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="recenterMap()" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition" title="Center on me">
                    <i class="fas fa-crosshairs text-sm"></i>
                </button>
                <a href="tel:{{ $branch->phone ?? '+255710603637' }}"
                   class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition" title="Call branch">
                    <i class="fas fa-phone text-sm"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Map -->
    <div id="map"></div>

    <!-- Bottom Status Bar -->
    <div id="statusBar" class="fixed bottom-0 left-0 right-0 z-[1000] bg-white border-t border-gray-200 px-4 py-4 shadow-[0_-4px_20px_rgba(0,0,0,0.15)]">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-location-crosshairs text-blue-600"></i>
                    </div>
                    <div>
                        <p id="distanceText" class="text-sm font-bold text-gray-800">Finding your location...</p>
                        <p id="etaText" class="text-xs text-gray-500">Please allow location access</p>
                    </div>
                </div>
                <div id="locationStatus" class="flex items-center gap-1 text-xs">
                    <span class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></span>
                    <span class="text-gray-400">Locating...</span>
                </div>
            </div>
            <div class="flex gap-2">
                <a id="googleMapsBtn" href="https://www.google.com/maps/dir/?api=1&destination={{ $branch->latitude }},{{ $branch->longitude }}&travelmode=driving"
                   target="_blank"
                   class="flex-1 py-3 bg-green-600 text-white text-sm font-bold rounded-xl text-center hover:bg-green-700 transition shadow-lg">
                    <i class="fas fa-diamond-turn-right mr-2"></i> Open in Google Maps
                </a>
                <a id="wazeBtn" href="https://www.waze.com/ul?ll={{ $branch->latitude }},{{ $branch->longitude }}&navigate=yes"
                   target="_blank"
                   class="py-3 px-4 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition shadow-lg">
                    <i class="fas fa-route"></i>
                </a>
            </div>
            <p class="text-[10px] text-gray-400 text-center mt-2"><i class="fas fa-info-circle mr-1"></i> For best GPS accuracy, use this on your phone</p>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 z-[2000] bg-amber-900 flex items-center justify-center">
        <div class="text-center text-white">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-white/10 flex items-center justify-center">
                <i class="fas fa-map-marker-alt text-2xl text-amber-300 animate-bounce"></i>
            </div>
            <h2 class="font-bold text-xl mb-1">Twende Dukani</h2>
            <p class="text-amber-200 text-sm">Loading map to {{ $branch->name }}...</p>
            <div class="mt-4 w-48 mx-auto h-1 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-amber-400 rounded-full animate-pulse" style="width: 60%"></div>
            </div>
        </div>
    </div>

    <script>
        const branchLat = {{ $branch->latitude }};
        const branchLng = {{ $branch->longitude }};
        const branchName = @json($branch->name);

        let map, customerMarker, branchMarker, routeLine;
        let watchId = null;
        let positions = [];

        // Initialize Leaflet map
        function initMap() {
            map = L.map('map', {
                center: [branchLat, branchLng],
                zoom: 15,
                zoomControl: false,
                attributionControl: false
            });

            // Add tile layer (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // Zoom control (top right)
            L.control.zoom({ position: 'topright' }).addTo(map);

            // Attribution (bottom right, small)
            L.control.attribution({ position: 'bottomright', prefix: false })
                .addAttribution('© OpenStreetMap')
                .addTo(map);

            // Branch marker
            const branchIcon = L.divIcon({
                className: '',
                html: '<div class="branch-marker"><i class="fas fa-store"></i></div>',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            branchMarker = L.marker([branchLat, branchLng], { icon: branchIcon })
                .addTo(map)
                .bindPopup(`<div style="font-family:Inter,sans-serif;padding:4px;">
                    <strong style="color:#92400e;">${branchName}</strong><br>
                    <small style="color:#6b7280;">Your destination</small>
                </div>`)
                .openPopup();

            // Start tracking
            startTracking();
        }

        function startTracking() {
            if (!navigator.geolocation) {
                document.getElementById('distanceText').textContent = 'Geolocation not supported';
                document.getElementById('etaText').textContent = 'Use Google Maps button below';
                document.getElementById('loadingOverlay').classList.add('hidden');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    updatePosition(pos);
                    document.getElementById('loadingOverlay').classList.add('hidden');
                },
                (err) => {
                    onError(err);
                    document.getElementById('loadingOverlay').classList.add('hidden');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );

            // Watch for real-time updates
            watchId = navigator.geolocation.watchPosition(
                updatePosition,
                onError,
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
            );
        }

        let positionCount = 0;
        let lastAccuratePosition = null;

        function updatePosition(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy || 0;
            positionCount++;

            // Check if location seems inaccurate (too far from branch — likely WiFi-based fallback)
            const dist = haversineDistance(lat, lng, branchLat, branchLng);
            const isLikelyAccurate = accuracy < 50000 && dist < 500;

            // Only use position if it seems reasonably accurate
            if (!isLikelyAccurate && positionCount <= 2) {
                // First few readings are way off — probably WiFi-based geolocation
                console.log('Location seems inaccurate (dist=' + dist.toFixed(0) + 'km, accuracy=' + accuracy.toFixed(0) + 'm). Waiting for better GPS fix...');
                document.getElementById('distanceText').textContent = 'Refining location...';
                document.getElementById('etaText').textContent = 'GPS accuracy improving (' + dist.toFixed(0) + ' km detected)';
                document.getElementById('locationStatus').innerHTML =
                    '<span class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></span><span class="text-yellow-600">GPS Fixing</span>';
                return;
            }

            // Store the most accurate position we've seen
            lastAccuratePosition = { lat, lng };

            // Customer marker icon
            const customerIcon = L.divIcon({
                className: '',
                html: '<div class="customer-marker"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });

            if (customerMarker) {
                customerMarker.setLatLng([lat, lng]);
            } else {
                customerMarker = L.marker([lat, lng], { icon: customerIcon })
                    .addTo(map)
                    .bindPopup('<strong style="color:#1d4ed8;">You are here</strong>');

                // If customer is close to branch, fit both; otherwise just focus on branch
                if (dist < 20) {
                    const bounds = L.latLngBounds([
                        [lat, lng],
                        [branchLat, branchLng]
                    ]);
                    map.fitBounds(bounds, { padding: [60, 60] });
                } else {
                    // Customer far from branch — just zoom to show branch area
                    map.setView([branchLat, branchLng], 15);
                }
            }

            // Draw route line
            if (routeLine) {
                map.removeLayer(routeLine);
            }
            routeLine = L.polyline(
                [[lat, lng], [branchLat, branchLng]],
                { color: '#f59e0b', weight: 4, dashArray: '10, 8', opacity: 0.8 }
            ).addTo(map);

            // Calculate distance
            const speed = 40; // avg speed km/h
            const etaMinutes = Math.round((dist / speed) * 60);

            document.getElementById('distanceText').textContent = dist.toFixed(1) + ' km away';
            document.getElementById('etaText').textContent = etaMinutes > 0 ? `~${etaMinutes} min by car` : 'You have arrived!';

            // Update status indicator
            document.getElementById('locationStatus').innerHTML =
                '<span class="w-2 h-2 rounded-full bg-green-400"></span><span class="text-green-600">Live</span>';

            // Update Google Maps link with origin
            const gMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${lat},${lng}&destination=${branchLat},${branchLng}&travelmode=driving`;
            document.getElementById('googleMapsBtn').href = gMapsUrl;

            // Update Waze link
            const wazeUrl = `https://www.waze.com/ul?ll=${branchLat},${branchLng}&navigate=yes&from=${lat},${lng}`;
            document.getElementById('wazeBtn').href = wazeUrl;
        }

        function onError(error) {
            let msg = 'Unable to get your location.';
            let sub = 'Tap Open in Google Maps below for navigation';
            if (error.code === 1) { msg = 'Location access denied'; sub = 'Allow location access in browser settings, or use Google Maps below'; }
            else if (error.code === 2) { msg = 'Location unavailable'; sub = 'Try on your phone for better accuracy, or use Google Maps below'; }
            else if (error.code === 3) { msg = 'Location request timed out'; sub = 'Retrying... Use Google Maps for best results'; }

            document.getElementById('distanceText').textContent = msg;
            document.getElementById('etaText').textContent = sub;
            document.getElementById('locationStatus').innerHTML =
                '<span class="w-2 h-2 rounded-full bg-red-400"></span><span class="text-red-500">Error</span>';
        }

        function recenterMap() {
            if (customerMarker) {
                const latlng = customerMarker.getLatLng();
                map.setView(latlng, 16);
            }
        }

        function haversineDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }

        // Initialize
        initMap();
    </script>
</body>
</html>
