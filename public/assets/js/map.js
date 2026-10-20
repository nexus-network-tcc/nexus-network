/* Nexus Network: mapas (Leaflet + agrupamento de marcadores) */
(function () {
    'use strict';

    if (!window.L) return;

    const TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    const ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';
    const GREEN = '#20cd8d';

    const baseLayer = () => L.tileLayer(TILES, { attribution: ATTRIBUTION, maxZoom: 19 });

    const escapeHtml = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    const stars = (rating) => {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            if (rating >= i) html += '<i class="fa-solid fa-star"></i>';
            else if (rating >= i - 0.5) html += '<i class="fa-solid fa-star-half-stroke"></i>';
            else html += '<i class="fa-regular fa-star"></i>';
        }
        return '<span class="stars">' + html + '</span>';
    };

    /* ---------- Mapa da busca ---------- */
    const resultsEl = document.querySelector('[data-results-map]');
    const dataEl = document.querySelector('[data-map-data]');
    if (resultsEl && dataEl) {
        const data = JSON.parse(dataEl.textContent);
        const map = L.map(resultsEl, { scrollWheelZoom: true, zoomControl: true });
        baseLayer().addTo(map);

        // Botão de expandir: o mapa ocupa a tela inteira (Esc ou o botão de novo para sair)
        const wrap = resultsEl.closest('.map-wrap');
        let expandBtn;
        const updateButton = (on) => {
            expandBtn.innerHTML = on ? '<i class="fa-solid fa-compress"></i>' : '<i class="fa-solid fa-expand"></i>';
            expandBtn.title = on ? 'Sair da tela cheia (Esc)' : 'Expandir mapa';
            expandBtn.setAttribute('aria-label', expandBtn.title);
            expandBtn.setAttribute('aria-pressed', String(on));
        };
        const setExpanded = (on) => {
            // Mantém o mesmo ponto central ao mudar o tamanho do mapa
            const center = map.getCenter();
            wrap.classList.toggle('is-expanded', on);
            document.body.classList.toggle('map-expanded', on);
            updateButton(on);
            setTimeout(() => { map.invalidateSize(); map.panTo(center, { animate: false }); }, 50);
        };
        const ExpandControl = L.Control.extend({
            options: { position: 'topright' },
            onAdd() {
                const box = L.DomUtil.create('div', 'leaflet-bar map-expand');
                expandBtn = L.DomUtil.create('a', '', box);
                expandBtn.href = '#';
                expandBtn.setAttribute('role', 'button');
                L.DomEvent.disableClickPropagation(box);
                L.DomEvent.on(expandBtn, 'click', (e) => {
                    L.DomEvent.preventDefault(e);
                    setExpanded(!wrap.classList.contains('is-expanded'));
                });
                return box;
            },
        });
        map.addControl(new ExpandControl());
        updateButton(false);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && wrap.classList.contains('is-expanded')) setExpanded(false);
        });

        const providerIcon = (p) => L.divIcon({
            className: 'map-pin' + (p.agora ? ' is-live' : ''),
            html: '<span>' + escapeHtml(p.iniciais) + '</span>',
            iconSize: [38, 38],
            iconAnchor: [19, 19],
            popupAnchor: [0, -20],
        });

        const clusters = L.markerClusterGroup({
            showCoverageOnHover: false,
            spiderfyOnMaxZoom: true,
            maxClusterRadius: 50,
            iconCreateFunction: (cluster) => L.divIcon({
                className: 'map-cluster',
                html: '<span>' + cluster.getChildCount() + '</span>',
                iconSize: [44, 44],
            }),
        });

        const bounds = [];
        data.pontos.forEach((p) => {
            const avatar = p.foto
                ? '<img src="' + escapeHtml(p.foto) + '" alt="" referrerpolicy="no-referrer">'
                : '<span>' + escapeHtml(p.iniciais) + '</span>';
            const rating = p.total > 0
                ? stars(p.media) + ' <strong>' + p.media.toFixed(1).replace('.', ',') + '</strong> <span class="muted">(' + p.total + ')</span>'
                : '<span class="muted">Sem avaliações</span>';
            const popup = '<div class="map-popup">'
                + '<div class="map-popup-head"><div class="map-popup-avatar">' + avatar + '</div><div>'
                + '<strong>' + escapeHtml(p.nome) + '</strong><span>' + escapeHtml(p.profissao) + '</span></div></div>'
                + '<div class="map-popup-rating">' + rating + '</div>'
                + '<div class="map-popup-meta"><span><i class="fa-solid fa-location-dot"></i> ' + escapeHtml(p.cidade)
                + (p.distancia ? ' · ' + escapeHtml(p.distancia) : '') + '</span>'
                + '<span><i class="fa-solid fa-tag"></i> ' + escapeHtml(p.preco) + '</span>'
                + (p.agora ? '<span class="text-green"><i class="fa-solid fa-circle"></i> Atendendo agora</span>' : '') + '</div>'
                + '<a class="btn btn-primary btn-sm btn-block" href="' + escapeHtml(p.url) + '">Ver perfil</a>'
                + '</div>';
            clusters.addLayer(L.marker([p.lat, p.lon], { icon: providerIcon(p), title: p.nome }).bindPopup(popup, { maxWidth: 280 }));
            bounds.push([p.lat, p.lon]);
        });
        map.addLayer(clusters);

        if (data.origem) {
            const me = L.marker([data.origem.lat, data.origem.lon], {
                icon: L.divIcon({ className: 'map-me', html: '<span></span>', iconSize: [22, 22] }),
                zIndexOffset: 1000,
                title: 'Você',
            }).bindPopup('<strong>Você</strong><br>' + escapeHtml(data.origem.label)).addTo(map);
            bounds.push(me.getLatLng());
            if (data.raio) {
                L.circle([data.origem.lat, data.origem.lon], {
                    radius: data.raio * 1000, color: GREEN, weight: 1.5, fillColor: GREEN, fillOpacity: 0.06, dashArray: '6 6',
                }).addTo(map);
            }
        }

        if (bounds.length) {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 13 });
        } else {
            map.setView([-22.12, -51.39], 8);
        }
    }

    /* ---------- Mini-mapa do perfil (região aproximada) ---------- */
    document.querySelectorAll('[data-mini-map]').forEach((el) => {
        const lat = parseFloat(el.dataset.lat);
        const lon = parseFloat(el.dataset.lon);
        if (Number.isNaN(lat) || Number.isNaN(lon)) return;

        // Arredonda o centro (~1 km) para não revelar a posição exata
        const center = [Math.round(lat * 100) / 100, Math.round(lon * 100) / 100];
        const map = L.map(el, {
            center, zoom: 12, zoomControl: false, dragging: false, scrollWheelZoom: false,
            doubleClickZoom: false, boxZoom: false, keyboard: false, touchZoom: false, attributionControl: true,
        });
        baseLayer().addTo(map);
        L.circle(center, { radius: 1500, color: GREEN, weight: 2, fillColor: GREEN, fillOpacity: 0.18 }).addTo(map);
    });
})();
