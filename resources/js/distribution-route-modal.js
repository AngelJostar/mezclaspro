import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const normalizeText = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('es')
    .replace(/\s+/g, ' ')
    .trim();

const parseJson = (elementId, fallback = []) => {
    const element = document.getElementById(elementId);

    if (!element) return fallback;

    try {
        return JSON.parse(element.textContent || '[]');
    } catch (error) {
        return fallback;
    }
};

const createElement = (tagName, className = '', text = '') => {
    const element = document.createElement(tagName);
    element.className = className;

    if (text !== '') element.textContent = text;

    return element;
};

const markerIcon = (hospital, selectedPosition = null, selectable = hospital.available, missingCoordinates = false) => {
    const stateClass = selectedPosition !== null
        ? 'is-selected'
        : (selectable ? 'is-available' : (missingCoordinates ? 'is-missing-coordinates' : 'is-assigned'));
    const content = selectedPosition !== null ? String(selectedPosition + 1) : '';

    return L.divIcon({
        className: 'route-hospital-marker-shell',
        html: `<span class="route-hospital-marker ${stateClass}">${content}</span>`,
        iconSize: selectedPosition !== null ? [30, 30] : [22, 22],
        iconAnchor: selectedPosition !== null ? [15, 15] : [11, 11],
        popupAnchor: [0, -16],
        tooltipAnchor: [0, -13],
    });
};

const coordinatesFor = (hospital) => [
    Number(hospital.latitude),
    Number(hospital.longitude),
];

const distanceBetween = (left, right) => {
    const [leftLatitude, leftLongitude] = coordinatesFor(left);
    const [rightLatitude, rightLongitude] = coordinatesFor(right);
    const latitudeDistance = leftLatitude - rightLatitude;
    const longitudeDistance = leftLongitude - rightLongitude;

    return (latitudeDistance ** 2) + (longitudeDistance ** 2);
};

function initializeDistributionRouteModal() {
    const modal = document.getElementById('route-create-modal');
    const typeModal = document.getElementById('route-type-modal');

    if (!modal || modal.dataset.initialized === 'true') return;

    modal.dataset.initialized = 'true';

    const form = document.getElementById('route-create-form');
    const methodInput = document.getElementById('route-form-method');
    const routeIdInput = document.getElementById('route-form-id');
    const routeTypeInput = document.getElementById('route-form-type');
    const modalTitle = document.getElementById('route-create-title');
    const modalSubtitle = document.getElementById('route-create-subtitle');
    const nameInput = document.getElementById('route-name');
    const messengerSelect = document.getElementById('route-messenger');
    const searchInput = document.getElementById('route-hospital-search');
    const suggestions = document.getElementById('route-hospital-suggestions');
    const mapElement = document.getElementById('route-hospital-map');
    const mapLoading = document.getElementById('route-map-loading');
    const mapError = document.getElementById('route-map-error');
    const selectedCount = document.getElementById('route-selected-count');
    const selectedEmpty = document.getElementById('route-selected-empty');
    const selectedList = document.getElementById('route-selected-list');
    const optimizeButton = document.getElementById('route-optimize-order');
    const hospitalInputs = document.getElementById('route-hospital-inputs');
    const saveButton = document.getElementById('route-save-button');
    const saveButtonLabel = saveButton?.querySelector('span');
    const typeNextButton = document.getElementById('route-type-next');
    const searchHelp = document.getElementById('route-search-help');
    const coordinateLegend = document.getElementById('route-coordinate-legend');
    const selectionGuidance = document.getElementById('route-selection-guidance');
    const routeTypeInputs = typeModal
        ? Array.from(typeModal.querySelectorAll('input[name="route_creation_type"]'))
        : [];

    if (!form || !methodInput || !routeIdInput || !routeTypeInput || !modalTitle || !modalSubtitle
        || !nameInput || !messengerSelect || !searchInput || !suggestions || !mapElement
        || !selectedCount || !selectedEmpty || !selectedList || !hospitalInputs || !saveButton) {
        return;
    }

    const hospitals = parseJson('route-hospital-data').map((hospital) => ({
        ...hospital,
        id: Number(hospital.id),
        available: Boolean(hospital.available),
        assigned_route_id: hospital.assigned_route_id === null
            ? null
            : Number(hospital.assigned_route_id),
        has_coordinates: Boolean(hospital.has_coordinates),
        latitude: Number(hospital.latitude),
        longitude: Number(hospital.longitude),
        searchText: normalizeText(`${hospital.name} ${hospital.address || ''}`),
    }));
    const hospitalsById = new Map(hospitals.map((hospital) => [hospital.id, hospital]));
    const initialMode = parseJson('route-initial-mode-data', { mode: 'create', hospital_ids: [] });
    const requestedEditingRouteId = Number(initialMode.route_id);
    let editingRouteId = initialMode.mode === 'edit' && requestedEditingRouteId > 0
        ? requestedEditingRouteId
        : null;
    const isDroneRoute = () => routeTypeInput.value === 'dron';
    const isHospitalAssignedElsewhere = (hospital) => Boolean(hospital) && !hospital.available
        && (editingRouteId === null || hospital.assigned_route_id !== editingRouteId);
    const isHospitalSelectable = (hospital) => Boolean(hospital)
        && !isHospitalAssignedElsewhere(hospital)
        && (!isDroneRoute() || hospital.has_coordinates);
    const normalizeHospitalIds = (ids) => (Array.isArray(ids) ? ids : [])
        .map(Number)
        .filter((id, index, values) => isHospitalSelectable(hospitalsById.get(id)) && values.indexOf(id) === index);
    let selectedIds = normalizeHospitalIds(initialMode.hospital_ids);
    let map = null;
    let routeLine = null;
    let activeSuggestionIndex = -1;
    let visibleSuggestions = [];
    let draggedHospitalId = null;
    let dropDestinationIndex = null;
    let mapLoadTimer = null;
    const markers = new Map();

    const isOpen = () => !modal.classList.contains('hidden');
    const isTypeOpen = () => Boolean(typeModal && !typeModal.classList.contains('hidden'));

    const setSuggestionVisibility = (visible) => {
        suggestions.classList.toggle('hidden', !visible);
        searchInput.setAttribute('aria-expanded', visible ? 'true' : 'false');

        if (!visible) {
            activeSuggestionIndex = -1;
            searchInput.removeAttribute('aria-activedescendant');
        }
    };

    const hideSuggestions = () => setSuggestionVisibility(false);

    const updateSaveState = () => {
        saveButton.disabled = nameInput.value.trim() === '' || messengerSelect.value === '' || selectedIds.length === 0;
    };

    const updateMapSelection = () => {
        if (!map) return;

        const selectedPositions = new Map(selectedIds.map((id, index) => [id, index]));

        markers.forEach((marker, hospitalId) => {
            const hospital = hospitalsById.get(hospitalId);
            const selectable = isHospitalSelectable(hospital);
            const missingCoordinates = isDroneRoute() && !hospital.has_coordinates;
            marker.setIcon(markerIcon(
                hospital,
                selectedPositions.get(hospitalId) ?? null,
                selectable,
                missingCoordinates,
            ));
            marker.setZIndexOffset(selectedPositions.has(hospitalId) ? 1000 : (selectable ? 200 : 0));
            marker.setPopupContent(popupContent(hospital));
        });

        const routeCoordinates = selectedIds
            .map((hospitalId) => hospitalsById.get(hospitalId))
            .filter(Boolean)
            .map(coordinatesFor);

        if (routeLine) {
            routeLine.setLatLngs(routeCoordinates);
        } else if (routeCoordinates.length > 1) {
            routeLine = L.polyline(routeCoordinates, {
                color: '#2563eb',
                opacity: 0.78,
                weight: 3,
                dashArray: '8 7',
            }).addTo(map);
        }

        if (routeLine && routeCoordinates.length < 2) {
            routeLine.setLatLngs([]);
        }
    };

    const moveHospital = (hospitalId, destinationIndex) => {
        const currentIndex = selectedIds.indexOf(hospitalId);

        if (currentIndex === -1) return;

        const nextIds = [...selectedIds];
        nextIds.splice(currentIndex, 1);
        nextIds.splice(Math.max(0, Math.min(destinationIndex, nextIds.length)), 0, hospitalId);
        selectedIds = nextIds;
        renderSelection();
    };

    const removeHospital = (hospitalId) => {
        selectedIds = selectedIds.filter((id) => id !== hospitalId);
        renderSelection();
    };

    const clearDropIndicator = () => {
        dropDestinationIndex = null;
        selectedList.querySelectorAll('.route-drop-before, .route-drop-after').forEach((item) => {
            item.classList.remove('route-drop-before', 'route-drop-after');
        });
    };

    const updateDropIndicator = (pointerY) => {
        if (draggedHospitalId === null) return;

        clearDropIndicator();

        const remainingIds = selectedIds.filter((id) => id !== draggedHospitalId);
        const remainingItems = Array.from(selectedList.querySelectorAll('li[data-hospital-id]'))
            .filter((item) => Number(item.dataset.hospitalId) !== draggedHospitalId);

        if (remainingItems.length === 0) return;

        const nextItem = remainingItems.find((item) => {
            const rectangle = item.getBoundingClientRect();
            return pointerY < rectangle.top + (rectangle.height / 2);
        });

        if (nextItem) {
            nextItem.classList.add('route-drop-before');
            dropDestinationIndex = remainingIds.indexOf(Number(nextItem.dataset.hospitalId));
            return;
        }

        remainingItems.at(-1).classList.add('route-drop-after');
        dropDestinationIndex = remainingIds.length;
    };

    const makeActionButton = (symbol, label, onClick, disabled = false) => {
        const button = createElement(
            'button',
            'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-35',
        );
        button.type = 'button';
        button.title = label;
        button.setAttribute('aria-label', label);
        button.disabled = disabled;

        const icon = createElement('span', 'text-sm font-bold leading-none');
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = symbol;
        button.append(icon);
        button.addEventListener('click', onClick);

        return button;
    };

    function renderSelection() {
        selectedCount.textContent = String(selectedIds.length);
        selectedEmpty.classList.toggle('hidden', selectedIds.length > 0);
        selectedEmpty.classList.toggle('flex', selectedIds.length === 0);
        selectedList.classList.toggle('hidden', selectedIds.length === 0);
        selectedList.replaceChildren();
        hospitalInputs.replaceChildren();

        selectedIds.forEach((hospitalId, index) => {
            const hospital = hospitalsById.get(hospitalId);
            if (!hospital) return;

            const item = createElement(
                'li',
                'group mb-2 grid grid-cols-[auto_auto_minmax(0,1fr)_auto] items-center gap-2 rounded-md border border-slate-200 bg-white p-2 shadow-sm',
            );
            item.draggable = true;
            item.dataset.hospitalId = String(hospitalId);

            const dragHandle = createElement('span', 'cursor-grab px-1 text-slate-400');
            dragHandle.title = 'Arrastrar para reordenar';
            dragHandle.textContent = '::';

            const position = createElement(
                'span',
                'inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-blue-600 px-2 text-xs font-bold text-white',
                String(index + 1),
            );

            const details = createElement('div', 'min-w-0');
            const hospitalName = createElement('p', 'truncate text-xs font-semibold text-slate-900', hospital.name);
            const hospitalAddress = createElement(
                'p',
                'mt-0.5 line-clamp-2 text-[11px] leading-4 text-slate-500',
                hospital.address || 'Direcci\u00f3n no registrada',
            );
            details.append(hospitalName, hospitalAddress);

            const actions = createElement('div', 'grid grid-cols-3 gap-1');
            actions.append(
                makeActionButton(
                    '&uarr;',
                    `Subir ${hospital.name}`,
                    () => moveHospital(hospitalId, index - 1),
                    index === 0,
                ),
                makeActionButton(
                    '&darr;',
                    `Bajar ${hospital.name}`,
                    () => moveHospital(hospitalId, index + 1),
                    index === selectedIds.length - 1,
                ),
                makeActionButton(
                    '&times;',
                    `Quitar ${hospital.name}`,
                    () => removeHospital(hospitalId),
                ),
            );

            item.append(dragHandle, position, details, actions);

            item.addEventListener('dragstart', (event) => {
                draggedHospitalId = hospitalId;
                item.classList.add('opacity-50');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(hospitalId));
            });
            item.addEventListener('dragend', () => {
                draggedHospitalId = null;
                item.classList.remove('opacity-50');
                clearDropIndicator();
            });

            selectedList.append(item);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'hospital_ids[]';
            input.value = String(hospitalId);
            input.setAttribute('value', String(hospitalId));
            hospitalInputs.append(input);
        });

        if (optimizeButton) optimizeButton.disabled = selectedIds.length < 3;
        updateSaveState();
        updateMapSelection();
    }

    selectedList.addEventListener('dragover', (event) => {
        if (draggedHospitalId === null) return;

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        updateDropIndicator(event.clientY);
    });

    selectedList.addEventListener('dragleave', (event) => {
        if (event.relatedTarget instanceof Node && selectedList.contains(event.relatedTarget)) return;
        clearDropIndicator();
    });

    selectedList.addEventListener('drop', (event) => {
        if (draggedHospitalId === null) return;

        event.preventDefault();

        const hospitalId = draggedHospitalId;
        const destinationIndex = dropDestinationIndex;
        draggedHospitalId = null;
        clearDropIndicator();

        if (destinationIndex !== null) moveHospital(hospitalId, destinationIndex);
    });

    const focusHospitalOnMap = (hospital) => {
        if (!map) return;

        map.flyTo(coordinatesFor(hospital), Math.max(map.getZoom(), 13), { duration: 0.6 });
        markers.get(hospital.id)?.openTooltip();
    };

    const addHospital = (hospitalId, focusMap = true) => {
        const hospital = hospitalsById.get(Number(hospitalId));

        if (!isHospitalSelectable(hospital)) return;

        if (!selectedIds.includes(hospital.id)) {
            selectedIds = [...selectedIds, hospital.id];
            renderSelection();
        }

        searchInput.value = '';
        hideSuggestions();
        if (focusMap) focusHospitalOnMap(hospital);
    };

    const toggleHospital = (hospital) => {
        if (!isHospitalSelectable(hospital)) return;

        if (selectedIds.includes(hospital.id)) {
            removeHospital(hospital.id);
        } else {
            addHospital(hospital.id, false);
        }
    };

    const popupContent = (hospital) => {
        const wrapper = createElement('div', 'min-w-[13rem]');
        wrapper.append(createElement('p', 'font-semibold text-slate-900', hospital.name));

        if (hospital.address) {
            wrapper.append(createElement('p', 'mt-1 text-xs leading-4 text-slate-500', hospital.address));
        }

        if (hospital.estimated) {
            wrapper.append(createElement('p', 'mt-2 text-[11px] text-amber-700', 'Ubicaci\u00f3n aproximada'));
        }

        if (isHospitalAssignedElsewhere(hospital)) {
            wrapper.append(createElement(
                'p',
                'mt-2 rounded bg-slate-100 px-2 py-1.5 text-xs font-medium text-slate-600',
                `Asignado a ${hospital.assigned_route || 'otra ruta'}`,
            ));
        }

        if (isDroneRoute() && !hospital.has_coordinates) {
            wrapper.append(createElement(
                'p',
                'mt-2 rounded bg-amber-50 px-2 py-1.5 text-xs font-medium text-amber-800',
                'Registra sus coordenadas para incluirlo en una ruta de dron.',
            ));
        }

        return wrapper;
    };

    const initializeMap = () => {
        if (map) {
            map.invalidateSize();
            return;
        }

        try {
            map = L.map(mapElement, {
                zoomControl: true,
                preferCanvas: true,
            }).setView([19.4326, -99.1332], 10);

            const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            });
            let tileErrors = 0;

            tiles.on('load', () => {
                mapLoading?.classList.add('hidden');
            });
            tiles.on('tileerror', () => {
                tileErrors += 1;
                if (tileErrors >= 3) mapError?.classList.remove('hidden');
            });
            tiles.addTo(map);

            const validHospitals = hospitals.filter((hospital) => (
                Number.isFinite(hospital.latitude) && Number.isFinite(hospital.longitude)
            ));

            validHospitals.forEach((hospital) => {
                const missingCoordinates = isDroneRoute() && !hospital.has_coordinates;
                const marker = L.marker(coordinatesFor(hospital), {
                    icon: markerIcon(hospital, null, isHospitalSelectable(hospital), missingCoordinates),
                    keyboard: true,
                    riseOnHover: true,
                    title: hospital.name,
                }).addTo(map);

                marker.bindTooltip(hospital.name, {
                    direction: 'top',
                    offset: [0, -12],
                    opacity: 0.96,
                });

                marker.bindPopup(popupContent(hospital), { closeButton: true, maxWidth: 300 });

                marker.on('click', () => {
                    if (isHospitalSelectable(hospital)) {
                        toggleHospital(hospital);
                        marker.openTooltip();
                    } else {
                        marker.openPopup();
                    }
                });
                markers.set(hospital.id, marker);
            });

            if (validHospitals.length > 1) {
                const bounds = L.latLngBounds(validHospitals.map(coordinatesFor));
                map.fitBounds(bounds.pad(0.08), { maxZoom: 11 });
            } else if (validHospitals.length === 1) {
                map.setView(coordinatesFor(validHospitals[0]), 13);
            }

            mapLoadTimer = window.setTimeout(() => {
                mapLoading?.classList.add('hidden');
            }, 2200);

            updateMapSelection();
        } catch (error) {
            mapLoading?.classList.add('hidden');
            mapError?.classList.remove('hidden');
        }
    };

    const setActiveSuggestion = (index) => {
        const options = Array.from(suggestions.querySelectorAll('[role="option"]'));

        if (options.length === 0) return;

        activeSuggestionIndex = (index + options.length) % options.length;
        options.forEach((option, optionIndex) => {
            option.classList.toggle('bg-blue-50', optionIndex === activeSuggestionIndex);
        });

        const activeOption = options[activeSuggestionIndex];
        searchInput.setAttribute('aria-activedescendant', activeOption.id);
        activeOption.scrollIntoView({ block: 'nearest' });
    };

    const renderSuggestions = () => {
        const query = normalizeText(searchInput.value);
        suggestions.replaceChildren();
        activeSuggestionIndex = -1;

        if (query.length < 1) {
            visibleSuggestions = [];
            hideSuggestions();
            return;
        }

        visibleSuggestions = hospitals
            .filter((hospital) => hospital.searchText.includes(query))
            .sort((left, right) => {
                const leftStarts = normalizeText(left.name).startsWith(query) ? 0 : 1;
                const rightStarts = normalizeText(right.name).startsWith(query) ? 0 : 1;

                return leftStarts - rightStarts
                    || Number(isHospitalSelectable(right)) - Number(isHospitalSelectable(left))
                    || left.name.localeCompare(right.name, 'es');
            })
            .slice(0, 8);

        if (visibleSuggestions.length === 0) {
            suggestions.append(createElement('p', 'px-4 py-3 text-sm text-slate-500', 'No se encontraron hospitales.'));
            setSuggestionVisibility(true);
            return;
        }

        visibleSuggestions.forEach((hospital, index) => {
            const selectable = isHospitalSelectable(hospital);
            const missingCoordinates = isDroneRoute() && !hospital.has_coordinates
                && !isHospitalAssignedElsewhere(hospital);
            const option = createElement(
                'button',
                `flex w-full items-start gap-3 border-b border-slate-100 px-4 py-3 text-left last:border-b-0 ${selectable ? 'hover:bg-blue-50' : (missingCoordinates ? 'cursor-not-allowed bg-amber-50/60' : 'cursor-not-allowed bg-slate-50')}`,
            );
            option.type = 'button';
            option.id = `route-hospital-option-${hospital.id}`;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', selectedIds.includes(hospital.id) ? 'true' : 'false');
            option.setAttribute('aria-disabled', selectable ? 'false' : 'true');
            option.dataset.suggestionIndex = String(index);

            const icon = createElement(
                'span',
                `mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-xs font-bold ${selectable ? 'bg-blue-50 text-blue-600' : (missingCoordinates ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-500')}`,
            );
            icon.textContent = 'H';

            const details = createElement('span', 'min-w-0 flex-1');
            details.append(createElement('span', 'block text-sm font-semibold text-slate-800', hospital.name));

            if (hospital.address) {
                details.append(createElement('span', 'mt-0.5 block truncate text-xs text-slate-500', hospital.address));
            }

            details.append(createElement(
                'span',
                `mt-1 block text-xs font-medium ${selectable ? 'text-blue-700' : 'text-slate-500'}`,
                selectable
                    ? (selectedIds.includes(hospital.id) ? 'Seleccionado' : 'Disponible')
                    : (missingCoordinates
                        ? 'Sin coordenadas registradas'
                        : `Asignado a ${hospital.assigned_route || 'otra ruta'}`),
            ));

            option.append(icon, details);
            option.addEventListener('mouseenter', () => setActiveSuggestion(index));
            option.addEventListener('click', () => {
                if (selectable) addHospital(hospital.id);
            });
            suggestions.append(option);
        });

        setSuggestionVisibility(true);
    };

    const configureMode = (routeData) => {
        const routeId = Number(routeData?.route_id);
        const isEditing = routeData?.mode === 'edit' && routeId > 0 && routeData.update_url;

        editingRouteId = isEditing ? routeId : null;
        form.action = isEditing ? routeData.update_url : form.dataset.createAction;
        methodInput.value = 'PATCH';
        methodInput.disabled = !isEditing;
        routeIdInput.value = isEditing ? String(routeId) : '';
        routeTypeInput.value = routeData?.route_type === 'dron' ? 'dron' : 'vehicular';
        modalTitle.textContent = isEditing ? 'Editar ruta' : 'Crear nueva ruta';
        modalSubtitle.textContent = isEditing
            ? 'Modifica el nombre, los hospitales o el orden del recorrido.'
            : (isDroneRoute()
                ? 'Selecciona hospitales con coordenadas registradas para formar el recorrido del dron.'
                : 'Selecciona uno a uno los hospitales que formarán parte del recorrido.');
        if (saveButtonLabel) saveButtonLabel.textContent = isEditing ? 'Guardar cambios' : 'Guardar ruta';

        coordinateLegend?.classList.toggle('hidden', !isDroneRoute());
        if (searchHelp) {
            searchHelp.textContent = isDroneRoute()
                ? 'Busca por nombre o dirección. Solo son seleccionables los hospitales con coordenadas registradas.'
                : 'Busca por nombre o dirección.';
        }
        if (selectionGuidance) {
            selectionGuidance.textContent = isDroneRoute()
                ? 'En rutas de dron solo puedes seleccionar hospitales disponibles con coordenadas registradas. Los hospitales en ámbar no tienen coordenadas y los puntos grises ya pertenecen a otra ruta.'
                : 'Solo puedes seleccionar hospitales disponibles en azul. Los puntos grises ya pertenecen a otra ruta. Las ubicaciones sin coordenadas registradas se muestran de forma aproximada.';
        }

        nameInput.value = String(routeData?.name || '');
        messengerSelect.value = routeData?.messenger_id ? String(routeData.messenger_id) : '';
        selectedIds = normalizeHospitalIds(routeData?.hospital_ids);
        searchInput.value = '';
        hideSuggestions();
        renderSelection();
    };

    const editDataFrom = (trigger) => {
        let hospitalIds = [];

        try {
            hospitalIds = JSON.parse(trigger.dataset.routeHospitalIds || '[]');
        } catch (error) {
            hospitalIds = [];
        }

        return {
            mode: 'edit',
            route_id: trigger.dataset.routeId,
            name: trigger.dataset.routeName,
            route_type: trigger.dataset.routeType,
            messenger_id: trigger.dataset.routeMessengerId,
            update_url: trigger.dataset.routeUpdateUrl,
            hospital_ids: hospitalIds,
        };
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.dataset.routeModalPreviousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        window.requestAnimationFrame(() => {
            initializeMap();
            window.requestAnimationFrame(() => map?.invalidateSize());
            nameInput.focus({ preventScroll: true });
        });
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        hideSuggestions();
        document.body.style.overflow = document.body.dataset.routeModalPreviousOverflow || '';
        delete document.body.dataset.routeModalPreviousOverflow;

        cleanRouteModalUrl();
    };

    function cleanRouteModalUrl() {
        const url = new URL(window.location.href);

        if (!url.searchParams.has('create') && !url.searchParams.has('edit')) return;

        url.searchParams.delete('create');
        url.searchParams.delete('edit');
        window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
    }

    const selectedRouteType = () => routeTypeInputs.find((input) => input.checked)?.value || 'vehicular';

    const updateRouteTypeSelection = () => {
        if (!typeModal || !typeNextButton) return;

        const selectedType = selectedRouteType();

        typeModal.querySelectorAll('[data-route-type-card]').forEach((card) => {
            const selected = card.dataset.routeTypeCard === selectedType;
            const indicator = card.querySelector('[data-route-type-indicator]');
            const indicatorDot = indicator?.firstElementChild;

            card.classList.toggle('border-blue-600', selected);
            card.classList.toggle('bg-blue-50/40', selected);
            card.classList.toggle('border-slate-200', !selected);
            card.classList.toggle('bg-white', !selected);
            indicator?.classList.toggle('border-blue-600', selected);
            indicator?.classList.toggle('bg-blue-600', selected);
            indicator?.classList.toggle('border-slate-300', !selected);
            indicator?.classList.toggle('bg-white', !selected);
            indicatorDot?.classList.toggle('bg-white', selected);
            indicatorDot?.classList.toggle('bg-transparent', !selected);
        });

        typeNextButton.disabled = !selectedType;
    };

    const openTypeModal = () => {
        if (!typeModal) return;

        const vehicleInput = routeTypeInputs.find((input) => input.value === 'vehicular');
        if (vehicleInput) vehicleInput.checked = true;
        updateRouteTypeSelection();

        typeModal.classList.remove('hidden');
        typeModal.classList.add('flex');
        typeModal.setAttribute('aria-hidden', 'false');
        document.body.dataset.routeTypeModalPreviousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        window.requestAnimationFrame(() => vehicleInput?.focus({ preventScroll: true }));
    };

    const closeTypeModal = (cleanUrl = true) => {
        if (!typeModal) return;

        typeModal.classList.add('hidden');
        typeModal.classList.remove('flex');
        typeModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = document.body.dataset.routeTypeModalPreviousOverflow || '';
        delete document.body.dataset.routeTypeModalPreviousOverflow;
        if (cleanUrl) cleanRouteModalUrl();
    };

    document.querySelectorAll('[data-route-type-open]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openTypeModal();
        });
    });

    routeTypeInputs.forEach((input) => input.addEventListener('change', updateRouteTypeSelection));

    typeModal?.querySelectorAll('[data-route-type-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => closeTypeModal());
    });

    typeNextButton?.addEventListener('click', () => {
        const routeType = selectedRouteType();

        closeTypeModal(false);
        configureMode({ mode: 'create', name: '', route_type: routeType, hospital_ids: [] });
        openModal();
    });

    document.querySelectorAll('[data-route-modal-edit]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            configureMode(editDataFrom(trigger));
            openModal();
        });
    });

    modal.querySelectorAll('[data-route-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', closeModal);
    });

    nameInput.addEventListener('input', updateSaveState);
    messengerSelect.addEventListener('change', updateSaveState);
    searchInput.addEventListener('input', renderSuggestions);
    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim() !== '') renderSuggestions();
    });
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (suggestions.classList.contains('hidden')) renderSuggestions();
            setActiveSuggestion(activeSuggestionIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveSuggestion(activeSuggestionIndex - 1);
        } else if (event.key === 'Enter' && activeSuggestionIndex >= 0) {
            event.preventDefault();
            const hospital = visibleSuggestions[activeSuggestionIndex];
            if (isHospitalSelectable(hospital)) addHospital(hospital.id);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            hideSuggestions();
        }
    });

    document.addEventListener('click', (event) => {
        if (!suggestions.contains(event.target) && event.target !== searchInput) hideSuggestions();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        if (isTypeOpen()) closeTypeModal();
        else if (isOpen() && suggestions.classList.contains('hidden')) closeModal();
    });

    optimizeButton?.addEventListener('click', () => {
        if (selectedIds.length < 3) return;

        const orderedIds = [selectedIds[0]];
        const remainingIds = selectedIds.slice(1);

        while (remainingIds.length > 0) {
            const currentHospital = hospitalsById.get(orderedIds.at(-1));
            let nearestIndex = 0;
            let nearestDistance = Number.POSITIVE_INFINITY;

            remainingIds.forEach((candidateId, candidateIndex) => {
                const candidateHospital = hospitalsById.get(candidateId);
                const distance = distanceBetween(currentHospital, candidateHospital);

                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearestIndex = candidateIndex;
                }
            });

            orderedIds.push(remainingIds.splice(nearestIndex, 1)[0]);
        }

        selectedIds = orderedIds;
        renderSelection();

        if (map && routeLine) {
            map.fitBounds(routeLine.getBounds().pad(0.2), { maxZoom: 13 });
        }
    });

    form.addEventListener('submit', (event) => {
        updateSaveState();

        if (saveButton.disabled) {
            event.preventDefault();
            (nameInput.value.trim() === '' ? nameInput : searchInput).focus();
            return;
        }

        saveButton.disabled = true;
        const label = saveButton.querySelector('span');
        if (label) label.textContent = 'Guardando...';
    });

    configureMode(initialMode);

    if (modal.dataset.openOnLoad === 'true') openModal();
    if (typeModal?.dataset.openOnLoad === 'true') openTypeModal();

    window.addEventListener('beforeunload', () => {
        if (mapLoadTimer) window.clearTimeout(mapLoadTimer);
        map?.remove();
    }, { once: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDistributionRouteModal, { once: true });
} else {
    initializeDistributionRouteModal();
}
