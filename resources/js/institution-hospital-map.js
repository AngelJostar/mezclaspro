import L from 'leaflet';
import { createIcons, LocateFixed } from 'lucide';
import 'leaflet/dist/leaflet.css';
import '../css/institution-hospital-map.css';

function initializeInstitutionHospitalMap() {
    const section = document.querySelector('[data-institution-hospital-map]');
    const element = section?.querySelector('.institution-hospital-map');
    if (!element || element.dataset.ready) return;
    element.dataset.ready = 'true';

    const loading = section.querySelector('[data-map-loading]');
    const error = section.querySelector('[data-map-error]');
    const selector = section.querySelector('[data-map-hospital]');
    const colorSelector = section.querySelector('[data-map-color-mode]');
    let map;
    let resizeObserver;
    let loadingTimeout;

    try {
        const hospitals = JSON.parse(section.querySelector('[data-map-hospitals]').textContent);
        const located = hospitals.filter((hospital) => (
            typeof hospital.latitude === 'number' && Number.isFinite(hospital.latitude)
            && typeof hospital.longitude === 'number' && Number.isFinite(hospital.longitude)
            && Math.abs(hospital.latitude) <= 90 && Math.abs(hospital.longitude) <= 180
        ));

        createIcons({ icons: { LocateFixed }, nameAttr: 'data-institution-map-icon', root: section });
        map = L.map(element, { scrollWheelZoom: false }).setView([23.6345, -102.5528], 5);
        const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        });
        const showTileError = () => {
            loading.hidden = true;
            error.hidden = false;
        };
        let tileFailures = 0;
        tiles.on('loading', () => { tileFailures = 0; });
        tiles.on('tileerror', () => {
            tileFailures += 1;
            if (tileFailures >= 3) showTileError();
        });
        tiles.on('load', () => {
            clearTimeout(loadingTimeout);
            loading.hidden = true;
            error.hidden = tileFailures === 0;
        });
        loadingTimeout = window.setTimeout(showTileError, 15000);
        tiles.addTo(map);

        const markers = new Map();
        located.forEach((hospital) => {
            const iconContent = document.createElement('span');
            iconContent.className = `institution-hospital-marker ${hospital.is_active ? 'is-active' : 'is-inactive'} ${hospital.has_assigned_route ? 'is-route-assigned' : 'is-route-unassigned'}`;
            if (hospital.estimated) iconContent.classList.add('institution-marker-estimated');

            const marker = L.marker([hospital.latitude, hospital.longitude], {
                icon: L.divIcon({
                    className: 'institution-hospital-marker-shell',
                    html: iconContent,
                    iconSize: [23, 23],
                    iconAnchor: [11.5, 11.5],
                }),
                title: hospital.name,
                alt: hospital.name,
                keyboard: true,
                riseOnHover: true,
            }).addTo(map);

            // Text nodes keep hospital names and addresses out of Leaflet's HTML parsing.
            const tooltip = document.createElement('span');
            tooltip.textContent = hospital.name;
            marker.bindTooltip(tooltip, { direction: 'top', offset: [0, -12] });
            const popup = document.createElement('div');
            popup.className = 'institution-hospital-map-popup';
            const name = document.createElement('strong');
            name.textContent = hospital.name;
            const address = document.createElement('p');
            address.textContent = hospital.address || 'Sin direcci\u00f3n registrada';
            const status = document.createElement('p');
            status.textContent = hospital.is_active ? 'Activo' : 'Inactivo';
            const routeStatus = document.createElement('p');
            routeStatus.textContent = hospital.has_assigned_route ? 'Ruta asignada' : 'Sin ruta asignada';
            const accuracy = document.createElement('p');
            accuracy.className = hospital.estimated ? 'is-estimated' : '';
            accuracy.textContent = hospital.estimated
                ? 'Ubicaci\u00f3n aproximada por localidad. Coordenadas exactas pendientes.'
                : 'Coordenadas registradas';
            popup.append(name, address, status, routeStatus, accuracy);
            marker.bindPopup(popup, {
                maxWidth: 250, maxHeight: 220, autoPanPadding: [16, 16],
                autoPanPaddingTopLeft: [16, 88], autoPanPaddingBottomRight: [16, 70], keepInView: true,
            });
            marker.on('click', () => { if (selector) selector.value = String(hospital.id); });
            markers.set(String(hospital.id), marker);
        });

        const applyColorMode = () => {
            const mode = colorSelector?.value === 'route' ? 'route' : 'status';
            section.dataset.colorMode = mode;
            section.querySelectorAll('[data-map-legend]').forEach((item) => {
                item.hidden = item.dataset.mapLegend !== mode;
            });
        };
        applyColorMode();
        colorSelector?.addEventListener('change', applyColorMode);

        const fitHospitals = () => {
            map.closePopup();
            if (selector) selector.value = '';
            if (located.length) {
                map.fitBounds(L.latLngBounds(located.map((hospital) => [hospital.latitude, hospital.longitude])), {
                    padding: [36, 36], maxZoom: located.length === 1 ? 13 : 12, animate: false,
                });
            }
        };
        fitHospitals();
        section.querySelector('[data-map-fit]')?.addEventListener('click', fitHospitals);
        selector?.addEventListener('change', () => {
            const marker = markers.get(selector.value);
            if (!marker) return fitHospitals();
            map.setView(marker.getLatLng(), 15, { animate: false });
            marker.openPopup();
        });
        resizeObserver = new ResizeObserver(() => map.invalidateSize({ pan: false }));
        resizeObserver.observe(element);
    } catch {
        clearTimeout(loadingTimeout);
        loading.hidden = true;
        error.textContent = 'No se pudo cargar el mapa de hospitales. Recarga la p\u00e1gina para reintentar.';
        error.hidden = false;
    }

    document.addEventListener('livewire:navigating', () => {
        clearTimeout(loadingTimeout);
        resizeObserver?.disconnect();
        map?.remove();
        delete element.dataset.ready;
    }, { once: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeInstitutionHospitalMap);
} else {
    initializeInstitutionHospitalMap();
}
document.addEventListener('livewire:navigated', initializeInstitutionHospitalMap);
