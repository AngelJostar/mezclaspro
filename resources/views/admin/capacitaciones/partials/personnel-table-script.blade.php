@push('js')
<script>
    @include('admin.catalogo-listas.partials.column-filter-script')

    window.addEventListener('load', function () {
        const table = document.getElementById('training-personnel-table');
        if (!table) return;

        const root = table.closest('[data-training-screen]');
        const tbody = table.tBodies[0];
        const rows = Array.from(tbody.querySelectorAll('[data-student-row]'));
        const originalOrder = new Map(rows.map((row, index) => [row, index]));
        const pagination = root.querySelector('[data-personnel-pagination]');
        const pageSize = Number(pagination.dataset.pageSize);
        const previous = pagination.querySelector('[data-personnel-previous]');
        const next = pagination.querySelector('[data-personnel-next]');
        const counter = pagination.querySelector('[data-personnel-count]');
        const empty = tbody.querySelector('[data-personnel-empty]');
        const department = root.querySelector('[data-personnel-department]');
        const sortButtons = Array.from(table.querySelectorAll('.js-personnel-sort'));
        const normalize = value => String(value ?? '').replace(/\s+/g, ' ').trim();
        let currentPage = Number(pagination.dataset.page);
        let orderedRows = [...rows];
        let activeSort = null;
        let initialized = false;
        let selectedDepartment = '';

        // Read displayed values, excluding avatars, edit forms and hidden access menus.
        const cellValue = (row, column) => {
            const cell = row.cells[column];
            if (!cell) return '';
            if (column === 0) return normalize(cell.querySelector('.training-person strong')?.textContent);
            if (column === 2) return Array.from(cell.querySelectorAll('.training-position-list > span')).map(position => {
                const copy = position.cloneNode(true);
                copy.querySelectorAll('small').forEach(label => label.remove());
                return normalize(copy.textContent);
            }).join(', ');
            if (column === 4 || column === 5) {
                const names = Array.from(cell.querySelectorAll(column === 4
                    ? '.training-completed-program > span' : '.training-current-program-heading > span'));
                return names.map(name => normalize(name.textContent)).join(', ') || 'Ninguno';
            }
            if (column === 6) return normalize(cell.querySelector('.training-score-summary strong')?.textContent) || 'Sin calificaciones';
            if (column === 8) return normalize(cell.querySelector('select')?.selectedOptions[0]?.textContent);
            if (column === 12 || column === 14) return normalize((cell.querySelector('[data-inline-value]') || cell).textContent);
            if (column === 19) return normalize((cell.querySelector('[data-access-status-menu-trigger]') || cell).textContent);
            return normalize(cell.textContent);
        };

        const sortableValue = (row, column, type) => {
            const value = cellValue(row, column);
            if (type === 'count') return row.cells[column].querySelectorAll(column === 4
                ? '.training-completed-program' : '.training-current-program').length;
            if (type === 'number') {
                const number = Number(value.replace('%', ''));
                return value && Number.isFinite(number) ? number : null;
            }
            if (type === 'date') {
                const parts = value.match(/^(\d{2})\/(\d{2})\/(\d{4})(?: (\d{2}):(\d{2}))?$/);
                return parts ? Date.UTC(+parts[3], +parts[2] - 1, +parts[1], +(parts[4] || 0), +(parts[5] || 0)) : null;
            }
            return value || null;
        };

        function sortRows() {
            if (!activeSort) return;
            const { column, direction, type } = activeSort;
            orderedRows = [...rows].sort((leftRow, rightRow) => {
                const left = sortableValue(leftRow, column, type);
                const right = sortableValue(rightRow, column, type);
                if (left === null && right !== null) return 1;
                if (left !== null && right === null) return -1;
                const comparison = left === null ? 0 : (typeof left === 'number' ? left - right
                    : left.localeCompare(right, 'es', { numeric: true, sensitivity: 'base' }));
                return (direction === 'asc' ? comparison : -comparison) || originalOrder.get(leftRow) - originalOrder.get(rightRow);
            });
            const currentRows = Array.from(tbody.querySelectorAll('[data-student-row]'));
            if (orderedRows.some((row, index) => row !== currentRows[index])) {
                const fragment = document.createDocumentFragment();
                orderedRows.forEach(row => fragment.appendChild(row));
                tbody.insertBefore(fragment, empty);
            }
        }

        function renderPage() {
            const matches = orderedRows.filter(row => row.dataset.columnFilterMatch !== '0'
                && (!selectedDepartment || cellValue(row, 7) === selectedDepartment));
            const pageCount = Math.max(1, Math.ceil(matches.length / pageSize));
            currentPage = Math.min(Math.max(1, currentPage), pageCount);
            const offset = (currentPage - 1) * pageSize;
            const visible = new Set(matches.slice(offset, offset + pageSize));
            rows.forEach(row => { row.hidden = !visible.has(row); });
            empty.hidden = matches.length > 0;
            counter.textContent = `${matches.length ? offset + 1 : 0}-${Math.min(offset + pageSize, matches.length)} de ${matches.length}`;
            previous.disabled = currentPage === 1;
            next.disabled = currentPage === pageCount;
        }

        const filters = window.createExcelColumnFilters({
            tableId: table.id,
            rowSelector: '[data-student-row]',
            triggerSelector: '.js-personnel-filter',
            instanceId: 'personnel',
            cellValue,
            onChange() {
                if (initialized) currentPage = 1;
                sortRows();
                renderPage();
                initialized = true;
            },
        });

        sortButtons.forEach(button => button.addEventListener('click', () => {
            const column = Number(button.dataset.sortColumn);
            const direction = activeSort?.column === column && activeSort.direction === 'asc' ? 'desc' : 'asc';
            activeSort = { column, direction, type: button.dataset.sortType };
            currentPage = 1;
            filters.close();
            sortRows();
            renderPage();
            sortButtons.forEach(sortButton => {
                const active = sortButton === button;
                sortButton.closest('th').setAttribute('aria-sort', active ? (direction === 'asc' ? 'ascending' : 'descending') : 'none');
                sortButton.setAttribute('aria-pressed', String(active));
                const label = `Ordenar ${sortButton.dataset.sortLabel} ${active && direction === 'asc' ? 'descendente' : 'ascendente'}`;
                sortButton.setAttribute('aria-label', label);
                sortButton.title = label;
                sortButton.classList.toggle('bg-blue-100', active);
                sortButton.classList.toggle('border-blue-400', active);
                sortButton.querySelector('[data-sort-icon]').dataset.personnelTableIcon = active
                    ? (direction === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrow-up-down';
            });
            document.dispatchEvent(new Event('personnel-table-sorted'));
        }));

        const applyDepartment = () => {
            selectedDepartment = department.value;
            currentPage = 1;
            renderPage();
        };
        department.addEventListener('change', applyDepartment);
        root.querySelector('[data-personnel-apply-department]').addEventListener('click', applyDepartment);
        root.querySelector('[data-personnel-clear-filters]').addEventListener('click', () => {
            department.value = '';
            selectedDepartment = '';
            filters.clear();
        });
        previous.addEventListener('click', () => { currentPage--; renderPage(); });
        next.addEventListener('click', () => { currentPage++; renderPage(); });
        table.addEventListener('change', () => filters.apply());

        // Existing personnel controls update statuses, roles and programs in place.
        let refreshTimer;
        new MutationObserver(() => {
            clearTimeout(refreshTimer);
            refreshTimer = setTimeout(() => filters.apply(), 50);
        }).observe(tbody, { childList: true, subtree: true, characterData: true });
    }, { once: true });
</script>
@endpush
