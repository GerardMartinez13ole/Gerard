<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Sql.php';

$config = require __DIR__ . '/config.php';
$sql = new Sql($config);

// obtenir rutes disponibles amb dades bàsiques
$rutes = $sql->select(
    "SELECT r.id, r.origin, r.destination, r.date_time, r.seats, r.description, u.nom as driver, u.correu as driver_email
     FROM rutes r
     JOIN usuaris u ON r.user_email = u.correu
     WHERE r.available = 1
     ORDER BY r.date_time ASC"
);

// Debug: si no hi ha rutes
if (empty($rutes)) {
    $rutes = [];
}
?>
<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Mapa - Rutes Disponibles - CarSharing</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
    <link rel="stylesheet" href="css/style_map.css">
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="container-fluid py-4">
        <div class="row g-3">
            <div class="col-12">
                <h1 class="h4 mb-1">Mapa de rutes disponibles</h1>
                <p class="text-muted mb-3">Vegeu des d'on surten totes les rutes. Cliqueu un marcador per veure detalls.</p>
                <div id="debug-info" class="alert alert-info small" style="display:none;"></div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-3">
                <div class="card mb-3">
                    <div class="card-body">
                        <!-- Afegeixo formulari de filtres -->
                        <form id="map-filter-form" class="mb-3">
                            <h6 class="mb-2"><i class="bi bi-funnel me-2"></i>Filtrar rutes</h6>
                            <div class="mb-2">
                                <label class="form-label small">Origen</label>
                                <input type="text" name="filter_origin" class="form-control form-control-sm" placeholder="Ex: Barcelona">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Destí</label>
                                <input type="text" name="filter_destination" class="form-control form-control-sm" placeholder="Ex: Girona">
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small">Data mínima</label>
                                    <input type="date" name="filter_date" class="form-control form-control-sm">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Places mínimes</label>
                                    <input type="number" name="filter_seats" min="0" max="8" class="form-control form-control-sm" placeholder="Qualsevol">
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filtrar</button>
                                <button type="reset" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Netejar</button>
                            </div>
                        </form>

                        <h5 class="card-title mb-2">Rutes (<span id="route-count"><?= count($rutes) ?></span>)</h5>
                        <div id="route-list" class="list-group list-group-flush">
                            <!-- la llista s'omplirà amb JS -->
                        </div>
                        <div class="mt-3">
                            <button id="fit-all" class="btn btn-outline-primary btn-sm">Ajustar a tots</button>
                        </div>
                    </div>
                </div>
                <div class="text-muted small">Els orígens es geocodifiquen automàticament; pot trigar uns segons la primera vegada.</div>
            </div>

            <div class="col-12 col-lg-9">
                <div id="map" style="height:72vh; background-color:#e8e8e8;"></div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Leaflet JS - IMPORTANT: Load BEFORE custom scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    // DEBUG MODE
    const DEBUG = false;
    
    // Comprovar que Leaflet s'ha carregat
    if (typeof L === 'undefined') {
        console.error('Leaflet no s\'ha carregat correctament');
        document.body.innerHTML = '<div class="alert alert-danger m-5">Error: Leaflet no s\'ha pogut carregar. Comprova la connexió a internet.</div>';
    }

    function debugLog(msg, data) {
        if (DEBUG) {
            console.log('[MAP DEBUG]', msg, data || '');
            const debugDiv = document.getElementById('debug-info');
            if (debugDiv) {
                debugDiv.style.display = 'block';
                debugDiv.innerHTML += msg + (data ? ' ' + JSON.stringify(data).substring(0,50) : '') + '<br>';
            }
        }
    }

    // Dades de rutes del servidor
    const routes = <?= json_encode($rutes, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    debugLog('Routes loaded:', routes.length + ' rutes');

    // Nou: obtenir focus_route de la query string (si existeix)
    const urlParams = new URLSearchParams(window.location.search);
    const focusRouteId = urlParams.has('focus_route') ? urlParams.get('focus_route') : null;

    // Inicialitza mapa
    let map;
    try {
        map = L.map('map', { scrollWheelZoom: true }).setView([41.5, 1.5], 7);
        debugLog('Map initialized successfully');
    } catch (e) {
        debugLog('Map init error:', e.message);
        console.error('Map error:', e);
    }

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const markers = [];
    const markersLayer = L.layerGroup().addTo(map);
    const routeLinesLayer = L.layerGroup().addTo(map);
    let currentRouteLine = null;
    let currentDestMarker = null; // marcador per al destí

    // nou: comptador per a punts duplicats (key = lat_lon amb precisió)
    let duplicateCounts = {};

    // Cache de geocodificació en localStorage
    const geocodeCacheKey = 'geo_cache_v1';
    let geoCache = {};
    try { 
        geoCache = JSON.parse(localStorage.getItem(geocodeCacheKey) || '{}'); 
        debugLog('GeoCache loaded:', Object.keys(geoCache).length + ' items');
    } catch(e){ 
        geoCache = {}; 
        debugLog('GeoCache error:', e.message);
    }

    async function geocodeOrigin(origin) {
        const key = origin.trim().toLowerCase();
        if (!key) {
            debugLog('Empty origin');
            return null;
        }
        if (geoCache[key]) {
            debugLog('Cache hit for:', key);
            return geoCache[key];
        }

        debugLog('Geocoding:', key);
        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(origin);
        try {
            const res = await fetch(url, { headers: { 'Accept-Language': 'ca' }});
            if (!res.ok) {
                debugLog('Nominatim error:', res.status);
                return null;
            }
            const data = await res.json();
            if (data && data.length) {
                const o = { lat: parseFloat(data[0].lat), lon: parseFloat(data[0].lon), display_name: data[0].display_name };
                geoCache[key] = o;
                try { localStorage.setItem(geocodeCacheKey, JSON.stringify(geoCache)); } catch(e){}
                debugLog('Geocoded successfully:', key);
                return o;
            } else {
                debugLog('Nominatim no result for:', key);
            }
        } catch(e) {
            debugLog('Geocode error:', e.message);
        }
        return null;
    }

    // Obtenir ruta entre dos punts (OSRM API)
    async function getRouteLine(originCoords, destCoords, route) {
        try {
            // abans de dibuixar: eliminar la ruta i marcador anteriors
            if (currentRouteLine) {
                routeLinesLayer.removeLayer(currentRouteLine);
                currentRouteLine = null;
            }
            if (currentDestMarker) {
                routeLinesLayer.removeLayer(currentDestMarker);
                currentDestMarker = null;
            }

            const url = `https://router.project-osrm.org/route/v1/driving/${originCoords.lon},${originCoords.lat};${destCoords.lon},${destCoords.lat}?overview=full&geometries=geojson`;
            const res = await fetch(url);
            if (!res.ok) throw new Error('OSRM error');
            const data = await res.json();
            
            if (data.routes && data.routes.length > 0) {
                const coords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                
                // Crear nova línia amb color i estil
                currentRouteLine = L.polyline(coords, {
                    color: '#0d6efd',
                    weight: 4,
                    opacity: 0.85,
                    dashArray: '6, 4'
                }).addTo(routeLinesLayer);
                
                // Afegir marcador al destí (icona petita)
                currentDestMarker = L.marker([destCoords.lat, destCoords.lon], {
                    title: 'Destí'
                }).addTo(routeLinesLayer);
                currentDestMarker.bindPopup(`<strong>Destí:</strong> ${escapeHtml(route.destination || '')}`);

                // Ajustar vista per veure tota la ruta
                const group = L.featureGroup([currentRouteLine, currentDestMarker]);
                map.fitBounds(group.getBounds().pad(0.15));
                
                debugLog('Route line drawn');
                return true;
            }
        } catch(e) {
            debugLog('Route line error:', e.message);
            // Fallback: línia recta simple (i marcador destí)
            return drawSimpleLine(originCoords, destCoords);
        }
        return false;
    }

    // Fallback: línea recta simple
    function drawSimpleLine(originCoords, destCoords) {
        // eliminar prèviament
        if (currentRouteLine) {
            routeLinesLayer.removeLayer(currentRouteLine);
            currentRouteLine = null;
        }
        if (currentDestMarker) {
            routeLinesLayer.removeLayer(currentDestMarker);
            currentDestMarker = null;
        }

        currentRouteLine = L.polyline(
            [[originCoords.lat, originCoords.lon], [destCoords.lat, destCoords.lon]],
            { color: '#6c757d', weight: 2, opacity: 0.5 }
        ).addTo(routeLinesLayer);

        currentDestMarker = L.marker([destCoords.lat, destCoords.lon], { title: 'Destí' }).addTo(routeLinesLayer);
        currentDestMarker.bindPopup(`<strong>Destí aproximat</strong>`);

        // ajustar vista per veure la línia
        const group = L.featureGroup([currentRouteLine, currentDestMarker]);
        map.fitBounds(group.getBounds().pad(0.15));
        return true;
    }

    // Afegeix marcador i llista lateral
    async function addRouteMarker(route, index) {
        const originText = route.origin || '';
        const ge = await geocodeOrigin(originText);
        if (!ge) {
            addRouteListItem(route, index, null);
            return;
        }

        // Geocodificar destí també
        const destCoords = await geocodeOrigin(route.destination || '');

        // Compute key using limited precision to detect duplicates
        const latKey = ge.lat.toFixed(6);
        const lonKey = ge.lon.toFixed(6);
        const coordKey = `${latKey}_${lonKey}`;
        duplicateCounts[coordKey] = (duplicateCounts[coordKey] || 0) + 1;
        const dupIndex = duplicateCounts[coordKey]; // 1-based

        // If duplicated, apply small offset for visibility (meters -> degrees approx)
        let markerLat = ge.lat;
        let markerLon = ge.lon;
        if (duplicateCounts[coordKey] > 1) {
            const radiusMeters = 25 + (dupIndex - 1) * 8; // increase radius for each duplicate
            const metersPerDegLat = 111320;
            const deltaLat = (radiusMeters / metersPerDegLat) * Math.cos((dupIndex * 45) * Math.PI / 180); 
            const metersPerDegLon = 111320 * Math.cos(ge.lat * Math.PI / 180);
            const deltaLon = (radiusMeters / (metersPerDegLon || 1)) * Math.sin((dupIndex * 45) * Math.PI / 180);

            markerLat = ge.lat + deltaLat;
            markerLon = ge.lon + deltaLon;
        }

        // Crear marcador en la posició possiblement desplaçada (per visibilitat)
        const marker = L.marker([markerLat, markerLon]).addTo(markersLayer);

        const popupHtml = `
            <div style="min-width:220px">
                <strong>#${route.id} ${escapeHtml(route.origin)} → ${escapeHtml(route.destination)}</strong><br>
                <small class="text-muted">${escapeHtml(new Date(route.date_time).toLocaleString('ca-ES'))}</small>
                <div class="mt-2"><strong>${escapeHtml(route.driver || '')}</strong></div>
                <div class="mt-2 small">${route.seats} places</div>
                <div class="mt-2">
                    <a class="btn btn-sm btn-primary" href="route_details.php?id=${encodeURIComponent(route.id)}">Veure detalls</a>
                </div>
            </div>`;
        marker.bindPopup(popupHtml);

        // Mostrar ruta quan es clica el marcador
        marker.on('click', async () => {
            // eliminar rastres anteriors abans de mostrar nova ruta
            if (currentRouteLine) {
                routeLinesLayer.removeLayer(currentRouteLine);
                currentRouteLine = null;
            }
            if (currentDestMarker) {
                routeLinesLayer.removeLayer(currentDestMarker);
                currentDestMarker = null;
            }

            if (destCoords) {
                await getRouteLine(ge, destCoords, route); // usa ge (sense offset) per a la ruta
            } else {
                marker.openPopup();
            }
        });

        markers.push(marker);
        addRouteListItem(route, index, marker, ge, destCoords);

        // Si aquesta ruta és la que hem passat per focus_route => obrir i dibuixar automàticament
        if (focusRouteId !== null && String(route.id) === String(focusRouteId)) {
            // obrir popup i dibuixar la ruta (si hi ha destí)
            marker.openPopup();
            if (destCoords) {
                // assegura's d'eliminar rutes anteriors abans de dibuixar
                if (currentRouteLine) { routeLinesLayer.removeLayer(currentRouteLine); currentRouteLine = null; }
                if (currentDestMarker) { routeLinesLayer.removeLayer(currentDestMarker); currentDestMarker = null; }
                await getRouteLine(ge, destCoords, route);
            } else {
                // centrar en el marcador si no hi ha destí
                map.setView(marker.getLatLng(), 13);
            }
            // un cop fet l'enfocament, evitar re-enfocar si l'usuari recarrega la mateixa
            // (si vols que romangui, elimina la següent línia)
            // focusRouteId = null; // no modificar const; si cal fer-ho, gestionar amb una variable let
        }
    }

    function addRouteListItem(route, index, marker, ge, destCoords) {
        const list = document.getElementById('route-list');
        const li = document.createElement('div');
        li.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
        li.style.cursor = marker ? 'pointer' : 'default';
        li.innerHTML = `
            <div style="flex:1; min-width:0;">
                <div class="fw-bold small" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">#${route.id} ${escapeHtml(route.origin)} → ${escapeHtml(route.destination)}</div>
                <div class="small text-muted">${escapeHtml(new Date(route.date_time).toLocaleString('ca-ES'))}</div>
            </div>
            <div>
                ${ ge ? '<button class="btn btn-sm btn-outline-primary open-marker">Anar</button>' : '<span class="small text-muted">No loc</span>'}
            </div>
        `;
        list.appendChild(li);
        if (marker) {
            li.querySelector('.open-marker').addEventListener('click', async (e)=>{
                e.stopPropagation();
                map.setView(marker.getLatLng(), 13);
                marker.openPopup();
                if (destCoords) {
                    await getRouteLine(ge, destCoords, route);
                }
            });
            li.addEventListener('click', async () => {
                map.setView(marker.getLatLng(), 13);
                marker.openPopup();
                if (destCoords) {
                    await getRouteLine(ge, destCoords, route);
                }
            });
        }
    }

    function escapeHtml(s) {
        if (!s) return '';
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    // --- NOVES FUNCIONS: filtratge i recarrega de marcadors ---
    function getFiltersFromForm() {
        const form = document.getElementById('map-filter-form');
        if (!form) return { origin: '', destination: '', date: '', seats: '' };
        return {
            origin: (form.querySelector('input[name="filter_origin"]').value || '').trim(),
            destination: (form.querySelector('input[name="filter_destination"]').value || '').trim(),
            date: (form.querySelector('input[name="filter_date"]').value || '').trim(),
            seats: (form.querySelector('input[name="filter_seats"]').value || '').trim()
        };
    }

    function applyFilters(filters) {
        // filters: {origin, destination, date, seats}
        return routes.filter(r => {
            if (filters.origin) {
                if (!r.origin || r.origin.toLowerCase().indexOf(filters.origin.toLowerCase()) === -1) return false;
            }
            if (filters.destination) {
                if (!r.destination || r.destination.toLowerCase().indexOf(filters.destination.toLowerCase()) === -1) return false;
            }
            if (filters.date) {
                // comparar només la part de la data
                try {
                    const routeDate = new Date(r.date_time);
                    const minDate = new Date(filters.date + 'T00:00:00');
                    if (isNaN(routeDate) || routeDate < minDate) return false;
                } catch(e) { return false; }
            }
            if (filters.seats) {
                const minSeats = parseInt(filters.seats, 10) || 0;
                if ((parseInt(r.seats, 10) || 0) < minSeats) return false;
            }
            return true;
        });
    }

    function clearMapState() {
        // eliminar marcadors existents
        markers.forEach(m => markersLayer.removeLayer(m));
        markers.length = 0;
        // eliminar línies i marcadors de ruta
        if (currentRouteLine) { routeLinesLayer.removeLayer(currentRouteLine); currentRouteLine = null; }
        if (currentDestMarker) { routeLinesLayer.removeLayer(currentDestMarker); currentDestMarker = null; }
        // buidar llista lateral i duplicats
        const list = document.getElementById('route-list');
        if (list) list.innerHTML = '';
        duplicateCounts = {};
    }

    async function loadMarkersForFilters(filters) {
        clearMapState();
        const filtered = applyFilters(filters);
        document.getElementById('route-count').innerText = filtered.length;
        if (filtered.length === 0) {
            document.getElementById('route-list').innerHTML = '<div class="text-muted small p-2">Cap ruta disponible</div>';
            return;
        }
        for (let i = 0; i < filtered.length; i++) {
            await addRouteMarker(filtered[i], i);
            // petit delay per evitar bloqueig per geocoding massiu
            await new Promise(r => setTimeout(r, 200));
        }
        debugLog('Markers loaded for filters, count:', filtered.length);
    }

    // inicialitzar valors del formulari a partir de la querystring (si hi ha)
    (function initFiltersFromQuery() {
        const form = document.getElementById('map-filter-form');
        if (!form) return;
        const origin = urlParams.get('filter_origin') || '';
        const destination = urlParams.get('filter_destination') || '';
        const date = urlParams.get('filter_date') || '';
        const seats = urlParams.get('filter_seats') || '';

        form.querySelector('input[name="filter_origin"]').value = origin;
        form.querySelector('input[name="filter_destination"]').value = destination;
        form.querySelector('input[name="filter_date"]').value = date;
        form.querySelector('input[name="filter_seats"]').value = seats;

        // submit del formulari actualitza la vista sense recarregar
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const f = getFiltersFromForm();
            // actualitzar querystring (replaceState)
            const newParams = new URLSearchParams(window.location.search);
            if (f.origin) newParams.set('filter_origin', f.origin); else newParams.delete('filter_origin');
            if (f.destination) newParams.set('filter_destination', f.destination); else newParams.delete('filter_destination');
            if (f.date) newParams.set('filter_date', f.date); else newParams.delete('filter_date');
            if (f.seats) newParams.set('filter_seats', f.seats); else newParams.delete('filter_seats');
            const newUrl = window.location.pathname + '?' + newParams.toString();
            history.replaceState(null, '', newUrl);

            await loadMarkersForFilters(f);
        });

        // reset: esborra params i recarrega tots
        form.querySelector('button[type="reset"]').addEventListener('click', async function() {
            setTimeout(async () => {
                // eliminar params rellevants
                const newParams = new URLSearchParams(window.location.search);
                newParams.delete('filter_origin'); newParams.delete('filter_destination');
                newParams.delete('filter_date'); newParams.delete('filter_seats');
                const newUrl = window.location.pathname + (newParams.toString() ? ('?' + newParams.toString()) : '');
                history.replaceState(null, '', newUrl);
                await loadMarkersForFilters({ origin:'', destination:'', date:'', seats:'' });
            }, 10);
        });
    })();

    // Carrega marcadors (inicial) -> aplica els filtres si venen per querystring
    (async function() {
        if (routes.length === 0) {
            debugLog('No routes to load');
            document.getElementById('route-list').innerHTML = '<div class="text-muted small p-2">Cap ruta disponible</div>';
            return;
        }

        const initialFilters = {
            origin: urlParams.get('filter_origin') || '',
            destination: urlParams.get('filter_destination') || '',
            date: urlParams.get('filter_date') || '',
            seats: urlParams.get('filter_seats') || ''
        };

        await loadMarkersForFilters(initialFilters);

        // botó fit-all
        document.getElementById('fit-all').addEventListener('click', () => {
            if (markers.length === 0) {
                debugLog('No markers to fit');
                return;
            }
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.15));
            // Eliminar línia i marcador de ruta al fer fit-all
            if (currentRouteLine) {
                routeLinesLayer.removeLayer(currentRouteLine);
                currentRouteLine = null;
            }
            if (currentDestMarker) {
                routeLinesLayer.removeLayer(currentDestMarker);
                currentDestMarker = null;
            }
            debugLog('Fitted to bounds');
        });
    })();

    </script>
</body>
</html>
