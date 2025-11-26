<!doctype html>
<html lang="ca">
<!-- Aquesta vista mostra un mapa interactiu amb les rutes disponibles. -->
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Mapa - Rutes Disponibles - CarSharing</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
    <link rel="stylesheet" href="css/style_map.css">
</head>
<body class="bg-light">
    <!-- Inclou la capçalera comuna amb la barra de navegació. -->
    <?php require_once 'includes/header.php'; ?>

    <main class="container-fluid py-4">
        <div class="row g-3">
            <!-- Títol de la pàgina. -->
            <div class="col-12">
                <h1 class="h4 mb-1">Mapa de rutes disponibles</h1>
                <p class="text-muted mb-3">Vegeu des d'on surten totes les rutes. Cliqueu un marcador per veure detalls.</p>
                <div id="debug-info" class="alert alert-info small" style="display:none;"></div>
            </div>
        </div>

        <div class="row">
            <!-- Columna esquerra: filtres i llista de rutes. -->
            <div class="col-12 col-lg-3">
                <div class="card mb-3">
                    <div class="card-body">
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

                        <!-- Llista de rutes que es generarà dinàmicament amb JavaScript. -->
                        <h5 class="card-title mb-2">Rutes (<span id="route-count"><?= count($rutes) ?></span>)</h5>
                        <div id="route-list" class="list-group list-group-flush">
                            </div>
                        <div class="mt-3">
                            <!-- Botó per ajustar el zoom del mapa per veure tots els marcadors. -->
                            <button id="fit-all" class="btn btn-outline-primary btn-sm">Ajustar a tots</button>
                        </div>
                    </div>
                </div>
                <div class="text-muted small">Els orígens es geocodifiquen automàticament; pot trigar uns segons la primera vegada.</div>
            </div>

            <!-- Columna dreta: contenidor del mapa. -->
            <div class="col-12 col-lg-9">
                <div id="map" style="height:72vh; background-color:#e8e8e8;"></div>
            </div>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>

    <!-- Inclusió de la llibreria Leaflet per a mapes interactius. -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    // Mode de depuració: si és `true`, mostra missatges a la consola.
    const DEBUG = false;
    
    // Comprovar que Leaflet s'ha carregat
    if (typeof L === 'undefined') {
        console.error('Leaflet no s\'ha carregat correctament');
        document.body.innerHTML = '<div class="alert alert-danger m-5">Error: Leaflet no s\'ha pogut carregar. Comprova la connexió a internet.</div>';
    }

    // Funció auxiliar per a la depuració.
    function debugLog(msg, data) {
        if (DEBUG) {
            console.log('[MAP DEBUG]', msg, data || '');
        }
    }

    // Dades de rutes del servidor (Injectades per PHP)
    // El controlador PHP passa la variable `$rutes` a la vista, i aquí la convertim a un objecte JavaScript.
    const routes = <?= json_encode($rutes, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    
    // Obtenir el paràmetre `focus_route` de la URL per centrar el mapa en una ruta específica en carregar.
    const urlParams = new URLSearchParams(window.location.search);
    const focusRouteId = urlParams.has('focus_route') ? urlParams.get('focus_route') : null;

    // Inicialització del mapa Leaflet.
    let map;
    try {
        // Crea l'objecte mapa, l'associa a l'element amb id="map" i estableix una vista inicial.
        map = L.map('map', { scrollWheelZoom: true }).setView([41.5, 1.5], 7);
    } catch (e) {
        console.error('Map error:', e);
    }

    // Afegeix una capa de "rajoles" (tiles) al mapa. Utilitzem OpenStreetMap com a proveïdor.
    // Aquesta capa és la que mostra el mapa base (carrers, ciutats, etc.).
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const markers = [];
    const markersLayer = L.layerGroup().addTo(map);
    const routeLinesLayer = L.layerGroup().addTo(map);
    // Variables per guardar la línia de ruta i el marcador de destí actuals, per poder esborrar-los fàcilment.
    let currentRouteLine = null;
    let currentDestMarker = null;

    // Objecte per comptar quants marcadors hi ha a la mateixa coordenada, per poder desplaçar-los.
    let duplicateCounts = {};
    // Clau per a la memòria cau (localStorage) de geocodificació.
    const geocodeCacheKey = 'geo_cache_v1';
    let geoCache = {};
    // Intentem carregar la memòria cau des del localStorage per no haver de geocodificar adreces ja conegudes.
    try { geoCache = JSON.parse(localStorage.getItem(geocodeCacheKey) || '{}'); } catch(e){ geoCache = {}; }

    /**
     * Converteix un nom de lloc (ex: "Lleida") en coordenades (latitud, longitud) usant l'API de Nominatim.
     * Utilitza una memòria cau (cache) per evitar crides repetides a l'API.
     * @param {string} origin - El nom del lloc a geocodificar.
     * @returns {Promise<object|null>} Un objecte amb {lat, lon, display_name} o null si no es troba.
     */
    async function geocodeOrigin(origin) {
        const key = origin.trim().toLowerCase();
        if (!key) return null;
        if (geoCache[key]) return geoCache[key];

        // Crida a l'API de Nominatim (servei gratuït d'OpenStreetMap).
        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(origin);
        try {
            const res = await fetch(url, { headers: { 'Accept-Language': 'ca' }});
            if (!res.ok) return null;
            const data = await res.json();
            if (data && data.length) {
                const o = { lat: parseFloat(data[0].lat), lon: parseFloat(data[0].lon), display_name: data[0].display_name };
                geoCache[key] = o;
                // Guardem el resultat a la memòria cau del navegador.
                try { localStorage.setItem(geocodeCacheKey, JSON.stringify(geoCache)); } catch(e){}
                return o;
            }
        } catch(e) {}
        return null;
    }

    /**
     * Obté i dibuixa la línia de la ruta entre dos punts usant l'API d'OSRM (Open Source Routing Machine).
     * @param {object} originCoords - Coordenades d'origen {lat, lon}.
     * @param {object} destCoords - Coordenades de destí {lat, lon}.
     * @param {object} route - L'objecte de la ruta per mostrar informació.
     * @returns {Promise<boolean>} `true` si s'ha pogut dibuixar la ruta, `false` si no.
     */
    async function getRouteLine(originCoords, destCoords, route) {
        try {
            if (currentRouteLine) { routeLinesLayer.removeLayer(currentRouteLine); currentRouteLine = null; }
            if (currentDestMarker) { routeLinesLayer.removeLayer(currentDestMarker); currentDestMarker = null; }
            // Crida a l'API pública d'OSRM per obtenir la geometria de la ruta per carretera.
            const url = `https://router.project-osrm.org/route/v1/driving/${originCoords.lon},${originCoords.lat};${destCoords.lon},${destCoords.lat}?overview=full&geometries=geojson`;
            const res = await fetch(url);
            if (!res.ok) throw new Error('OSRM error');
            const data = await res.json();
            
            if (data.routes && data.routes.length > 0) {
                const coords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                // Dibuixa la línia (Polyline) i el marcador de destí al mapa.
                currentRouteLine = L.polyline(coords, { color: '#0d6efd', weight: 4, opacity: 0.85, dashArray: '6, 4' }).addTo(routeLinesLayer);
                currentDestMarker = L.marker([destCoords.lat, destCoords.lon], { title: 'Destí' }).addTo(routeLinesLayer);
                currentDestMarker.bindPopup(`<strong>Destí:</strong> ${escapeHtml(route.destination || '')}`);

                const group = L.featureGroup([currentRouteLine, currentDestMarker]);
                // Ajusta el zoom del mapa per encabir tota la ruta.
                map.fitBounds(group.getBounds().pad(0.15));
                return true;
            }
        } catch(e) {
            return drawSimpleLine(originCoords, destCoords);
        }
        return false;
    }

    /**
     * Funció de "fallback" que dibuixa una línia recta simple si l'API d'OSRM falla.
     * @param {object} originCoords - Coordenades d'origen.
     * @param {object} destCoords - Coordenades de destí.
     */
    function drawSimpleLine(originCoords, destCoords) {
        if (currentRouteLine) { routeLinesLayer.removeLayer(currentRouteLine); currentRouteLine = null; }
        if (currentDestMarker) { routeLinesLayer.removeLayer(currentDestMarker); currentDestMarker = null; }

        currentRouteLine = L.polyline(
            [[originCoords.lat, originCoords.lon], [destCoords.lat, destCoords.lon]],
            { color: '#6c757d', weight: 2, opacity: 0.5 }
        ).addTo(routeLinesLayer);

        currentDestMarker = L.marker([destCoords.lat, destCoords.lon], { title: 'Destí' }).addTo(routeLinesLayer);
        currentDestMarker.bindPopup(`<strong>Destí aproximat</strong>`);
        const group = L.featureGroup([currentRouteLine, currentDestMarker]);
        map.fitBounds(group.getBounds().pad(0.15));
        return true;
    }

    /**
     * Afegeix un marcador al mapa per a una ruta específica.
     * @param {object} route - L'objecte de la ruta.
     * @param {number} index - L'índex de la ruta a la llista.
     */
    async function addRouteMarker(route, index) {
        const originText = route.origin || '';
        const ge = await geocodeOrigin(originText);
        if (!ge) {
            addRouteListItem(route, index, null);
            return;
        }

        const destCoords = await geocodeOrigin(route.destination || '');
        const latKey = ge.lat.toFixed(6);
        const lonKey = ge.lon.toFixed(6);
        const coordKey = `${latKey}_${lonKey}`;
        duplicateCounts[coordKey] = (duplicateCounts[coordKey] || 0) + 1;
        const dupIndex = duplicateCounts[coordKey];

        let markerLat = ge.lat;
        let markerLon = ge.lon;
        // Si hi ha múltiples marcadors a la mateixa ubicació, els desplaça lleugerament en espiral.
        if (duplicateCounts[coordKey] > 1) {
            const radiusMeters = 25 + (dupIndex - 1) * 8; 
            const metersPerDegLat = 111320;
            const deltaLat = (radiusMeters / metersPerDegLat) * Math.cos((dupIndex * 45) * Math.PI / 180); 
            const metersPerDegLon = 111320 * Math.cos(ge.lat * Math.PI / 180);
            const deltaLon = (radiusMeters / (metersPerDegLon || 1)) * Math.sin((dupIndex * 45) * Math.PI / 180);
            markerLat = ge.lat + deltaLat;
            markerLon = ge.lon + deltaLon;
        }

        const marker = L.marker([markerLat, markerLon]).addTo(markersLayer);

        // Contingut del popup que apareix en fer clic al marcador. Inclou un enllaç MVC a la pàgina de detalls.
        const popupHtml = `
            <div style="min-width:220px">
                <strong>#${route.id} ${escapeHtml(route.origin)} → ${escapeHtml(route.destination)}</strong><br>
                <small class="text-muted">${escapeHtml(new Date(route.date_time).toLocaleString('ca-ES'))}</small>
                <div class="mt-2"><strong>${escapeHtml(route.driver || '')}</strong></div>
                <div class="mt-2 small">${route.seats} places</div>
                <div class="mt-2">
                    <a class="btn btn-sm btn-primary" href="index.php?action=route_details&id=${encodeURIComponent(route.id)}">Veure detalls</a>
                </div>
            </div>`;
        marker.bindPopup(popupHtml);

        // Quan es fa clic a un marcador, intenta dibuixar la línia de la ruta fins al destí.
        marker.on('click', async () => {
            if (currentRouteLine) { routeLinesLayer.removeLayer(currentRouteLine); currentRouteLine = null; }
            if (currentDestMarker) { routeLinesLayer.removeLayer(currentDestMarker); currentDestMarker = null; }

            if (destCoords) {
                await getRouteLine(ge, destCoords, route);
            } else {
                marker.openPopup();
            }
        });

        markers.push(marker);
        addRouteListItem(route, index, marker, ge, destCoords);

        // Si aquesta és la ruta que s'ha demanat enfocar des de la URL, l'obre automàticament.
        if (focusRouteId !== null && String(route.id) === String(focusRouteId)) {
            marker.openPopup();
            if (destCoords) {
                if (currentRouteLine) { routeLinesLayer.removeLayer(currentRouteLine); currentRouteLine = null; }
                if (currentDestMarker) { routeLinesLayer.removeLayer(currentDestMarker); currentDestMarker = null; }
                await getRouteLine(ge, destCoords, route);
            } else {
                map.setView(marker.getLatLng(), 13);
            }
        }
    }

    /**
     * Afegeix un element a la llista de rutes de la columna esquerra.
     * @param {object} route - L'objecte de la ruta.
     * @param {number} index - L'índex de la ruta.
     * @param {L.Marker} marker - El marcador de Leaflet associat (pot ser null).
     * @param {object} ge - Les coordenades geocodificades de l'origen.
     */
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
            // Afegeix esdeveniments de clic per centrar el mapa i obrir el popup corresponent.
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

    // Funció de seguretat per evitar atacs XSS (Cross-Site Scripting) a l'injectar text a l'HTML.
    function escapeHtml(s) {
        if (!s) return '';
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    /**
     * Llegeix els valors actuals del formulari de filtre.
     * @returns {object} Un objecte amb els valors dels filtres.
     */
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

    /**
     * Filtra l'array global `routes` segons els criteris rebuts.
     * @param {object} filters - L'objecte amb els filtres a aplicar.
     * @returns {Array} Un nou array amb les rutes que compleixen els filtres.
     */
    function applyFilters(filters) {
        return routes.filter(r => {
            if (filters.origin && (!r.origin || r.origin.toLowerCase().indexOf(filters.origin.toLowerCase()) === -1)) return false;
            if (filters.destination && (!r.destination || r.destination.toLowerCase().indexOf(filters.destination.toLowerCase()) === -1)) return false;
            if (filters.date) {
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

    /**
     * Neteja l'estat del mapa: esborra marcadors, línies i la llista de rutes.
     */
    function clearMapState() {
        markers.forEach(m => markersLayer.removeLayer(m));
        markers.length = 0;
        if (currentRouteLine) { routeLinesLayer.removeLayer(currentRouteLine); currentRouteLine = null; }
        if (currentDestMarker) { routeLinesLayer.removeLayer(currentDestMarker); currentDestMarker = null; }
        const list = document.getElementById('route-list');
        if (list) list.innerHTML = '';
        duplicateCounts = {};
    }

    /**
     * Carrega els marcadors al mapa basant-se en els filtres aplicats.
     * @param {object} filters - L'objecte de filtres.
     */
    async function loadMarkersForFilters(filters) {
        clearMapState();
        const filtered = applyFilters(filters);
        document.getElementById('route-count').innerText = filtered.length;
        if (filtered.length === 0) {
            document.getElementById('route-list').innerHTML = '<div class="text-muted small p-2">Cap ruta disponible</div>';
            return;
        }
        for (let i = 0; i < filtered.length; i++) {
            // Processa cada ruta per afegir el seu marcador.
            await addRouteMarker(filtered[i], i);
            // Petita pausa per no sobrecarregar l'API de geocodificació amb moltes peticions simultànies.
            await new Promise(r => setTimeout(r, 100));
        }
    }

    // Funció auto-executable (IIFE) que inicialitza els filtres i els seus esdeveniments.
    (function initFiltersFromQuery() {
        const form = document.getElementById('map-filter-form');
        if (!form) return;
        
        // Cargar filtres des de la URL si n'hi ha
        form.querySelector('input[name="filter_origin"]').value = urlParams.get('filter_origin') || '';
        form.querySelector('input[name="filter_destination"]').value = urlParams.get('filter_destination') || '';
        form.querySelector('input[name="filter_date"]').value = urlParams.get('filter_date') || '';
        form.querySelector('input[name="filter_seats"]').value = urlParams.get('filter_seats') || '';

        // Esdeveniment d'enviament del formulari de filtre.
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const f = getFiltersFromForm();
            const newParams = new URLSearchParams(window.location.search);
            // Actualitza la URL del navegador amb els paràmetres de filtre, sense recarregar la pàgina.
            // Mantenim l'acció (action=mapa)
            newParams.set('action', 'mapa');
            if (f.origin) newParams.set('filter_origin', f.origin); else newParams.delete('filter_origin');
            if (f.destination) newParams.set('filter_destination', f.destination); else newParams.delete('filter_destination');
            if (f.date) newParams.set('filter_date', f.date); else newParams.delete('filter_date');
            if (f.seats) newParams.set('filter_seats', f.seats); else newParams.delete('filter_seats');
            const newUrl = window.location.pathname + '?' + newParams.toString();
            history.replaceState(null, '', newUrl);

            await loadMarkersForFilters(f);
        });

        // Esdeveniment del botó de netejar el formulari.
        form.querySelector('button[type="reset"]').addEventListener('click', async function() {
            setTimeout(async () => {
                await loadMarkersForFilters({ origin:'', destination:'', date:'', seats:'' });
            }, 10);
        });
    })();

    // Funció principal auto-executable que s'executa en carregar la pàgina.
    (async function() {
        if (routes.length === 0) {
            document.getElementById('route-list').innerHTML = '<div class="text-muted small p-2">Cap ruta disponible</div>';
            return;
        }

        // Llegeix els filtres inicials de la URL per carregar l'estat correcte.
        const initialFilters = {
            origin: urlParams.get('filter_origin') || '',
            destination: urlParams.get('filter_destination') || '',
            date: urlParams.get('filter_date') || '',
            seats: urlParams.get('filter_seats') || ''
        };

        // Carrega els marcadors amb els filtres inicials.
        await loadMarkersForFilters(initialFilters);

        // Esdeveniment del botó "Ajustar a tots".
        document.getElementById('fit-all').addEventListener('click', () => {
            if (markers.length === 0) return;
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.15));
            if (currentRouteLine) { routeLinesLayer.removeLayer(currentRouteLine); currentRouteLine = null; }
            if (currentDestMarker) { routeLinesLayer.removeLayer(currentDestMarker); currentDestMarker = null; }
        });
    })();
    </script>
</body>
</html>