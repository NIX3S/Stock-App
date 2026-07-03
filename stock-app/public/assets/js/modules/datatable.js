/**
 * Module datatable générique : server-side processing (tri/filtre/pagination
 * traités en SQL côté API), colonnes masquables et réorganisables, recherche
 * instantanée (debounce), préférences persistées par utilisateur et par
 * tableau via /api/preferences/{tableKey}.
 */
export class DataTable {
    constructor(root) {
        this.root = root;
        this.tableKey = root.dataset.tableKey;
        this.apiUrl = root.dataset.apiUrl;
        this.columns = JSON.parse(root.querySelector('.dt-columns-config').textContent);
        this.state = {
            page: 1,
            perPage: 25,
            search: '',
            sort: this.columns.find(c => c.sortable)?.key || this.columns[0].key,
            dir: 'asc',
            visibleColumns: this.columns.map(c => c.key),
            columnOrder: this.columns.map(c => c.key),
        };

        this.headerRow = root.querySelector('.dt-header-row');
        this.body = root.querySelector('.dt-body');
        this.summary = root.querySelector('.dt-summary');
        this.pagination = root.querySelector('.dt-pagination');
        this.columnToggle = root.querySelector('.dt-column-toggle');
        this.searchInput = root.querySelector('.dt-search');

        this.init();
    }

    async init() {
        await this.loadPreferences();
        this.renderColumnToggle();
        this.renderHeader();
        this.bindEvents();
        this.fetchAndRender();
    }

    async loadPreferences() {
        try {
            const res = await fetch(`/api/preferences/${this.tableKey}`);
            const data = await res.json();
            if (data.preferences) {
                if (data.preferences.visible_columns?.length) this.state.visibleColumns = data.preferences.visible_columns;
                if (data.preferences.column_order?.length) this.state.columnOrder = data.preferences.column_order;
                if (data.preferences.sort?.column) this.state.sort = data.preferences.sort.column;
                if (data.preferences.sort?.dir) this.state.dir = data.preferences.sort.dir;
            }
        } catch (e) {
            // Pas de préférences enregistrées : on garde les valeurs par défaut.
        }
    }

    savePreferences() {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch(`/api/preferences/${this.tableKey}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({
                visible_columns: this.state.visibleColumns,
                column_order: this.state.columnOrder,
                filters: {},
                sort: { column: this.state.sort, dir: this.state.dir },
            }),
        }).catch(() => {});
    }

    orderedVisibleColumns() {
        return this.state.columnOrder
            .filter(key => this.state.visibleColumns.includes(key))
            .map(key => this.columns.find(c => c.key === key))
            .filter(Boolean);
    }

    renderColumnToggle() {
        this.columnToggle.innerHTML = '';
        this.columns.forEach(col => {
            const id = `col-toggle-${this.tableKey}-${col.key}`;
            const wrapper = document.createElement('div');
            wrapper.className = 'form-check';
            wrapper.innerHTML = `
                <input class="form-check-input" type="checkbox" id="${id}" ${this.state.visibleColumns.includes(col.key) ? 'checked' : ''}>
                <label class="form-check-label" for="${id}">${col.label}</label>`;
            wrapper.querySelector('input').addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.state.visibleColumns.push(col.key);
                } else {
                    this.state.visibleColumns = this.state.visibleColumns.filter(k => k !== col.key);
                }
                this.renderHeader();
                this.savePreferences();
                this.fetchAndRender();
            });
            this.columnToggle.appendChild(wrapper);
        });
    }

    renderHeader() {
        this.headerRow.innerHTML = '';
        this.orderedVisibleColumns().forEach((col, index) => {
            const th = document.createElement('th');
            th.textContent = col.label;
            th.draggable = true;
            th.dataset.key = col.key;
            th.style.cursor = col.sortable ? 'pointer' : 'grab';
            if (col.sortable) {
                th.addEventListener('click', () => {
                    this.state.dir = (this.state.sort === col.key && this.state.dir === 'asc') ? 'desc' : 'asc';
                    this.state.sort = col.key;
                    this.savePreferences();
                    this.fetchAndRender();
                });
                if (this.state.sort === col.key) {
                    th.textContent += this.state.dir === 'asc' ? ' ▲' : ' ▼';
                }
            }
            // Réorganisation des colonnes par glisser-déposer
            th.addEventListener('dragstart', (e) => e.dataTransfer.setData('text/plain', col.key));
            th.addEventListener('dragover', (e) => e.preventDefault());
            th.addEventListener('drop', (e) => {
                e.preventDefault();
                const draggedKey = e.dataTransfer.getData('text/plain');
                const order = this.state.columnOrder.filter(k => k !== draggedKey);
                const targetIndex = order.indexOf(col.key);
                order.splice(targetIndex, 0, draggedKey);
                this.state.columnOrder = order;
                this.renderHeader();
                this.savePreferences();
                this.fetchAndRender();
            });
            this.headerRow.appendChild(th);
        });
        const actionsTh = document.createElement('th');
        actionsTh.textContent = '';
        this.headerRow.appendChild(actionsTh);
    }

    bindEvents() {
        let debounce;
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                this.state.search = e.target.value;
                this.state.page = 1;
                this.fetchAndRender();
            }, 300);
        });

        this.root.querySelector('.dt-export-csv')?.addEventListener('click', () => {
            window.location.href = '/export/products.csv';
        });
        this.root.querySelector('.dt-export-xlsx')?.addEventListener('click', () => {
            window.location.href = '/export/products.xlsx';
        });
        this.root.querySelector('.dt-print')?.addEventListener('click', () => {
            window.open('/print/products', '_blank');
        });
    }

    async fetchAndRender() {
        const params = new URLSearchParams({
            page: this.state.page,
            per_page: this.state.perPage,
            search: this.state.search,
            sort: this.state.sort,
            dir: this.state.dir,
        });

        this.body.innerHTML = '<tr><td class="text-center text-muted py-4">Chargement...</td></tr>';

        try {
            const res = await fetch(`${this.apiUrl}?${params}`);
            const json = await res.json();
            this.renderRows(json.data);
            this.renderPagination(json.total, json.page, json.per_page);
        } catch (e) {
            this.body.innerHTML = '<tr><td class="text-center text-danger py-4">Erreur de chargement.</td></tr>';
        }
    }

    renderRows(rows) {
        const visible = this.orderedVisibleColumns();
        this.body.innerHTML = '';

        if (!rows.length) {
            this.body.innerHTML = `<tr><td colspan="${visible.length + 1}" class="text-center text-muted py-4">Aucun résultat.</td></tr>`;
            return;
        }

        // Liens d'action selon le tableau
        const actionLinks = {
            'products_list':      (row) => `<a href="/products/${row.id}" class="btn btn-sm btn-outline-primary">Voir</a>`,
            'stock_entries_list': (row) => `<a href="/stock-entries/${row.id}/edit" class="btn btn-sm btn-outline-secondary">Modifier</a>`,
        };
        const getAction = actionLinks[this.tableKey] ?? ((row) => `<a href="#" class="btn btn-sm btn-outline-secondary">${row.id}</a>`);

        rows.forEach(row => {
            const tr = document.createElement('tr');
            visible.forEach(col => {
                const td = document.createElement('td');
                td.textContent = row[col.key] ?? '';
                tr.appendChild(td);
            });
            const actionsTd = document.createElement('td');
            actionsTd.innerHTML = getAction(row);
            tr.appendChild(actionsTd);
            this.body.appendChild(tr);
        });
    }

    renderPagination(total, page, perPage) {
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        this.summary.textContent = `${total} résultat(s) — page ${page} / ${totalPages}`;
        this.pagination.innerHTML = '';

        const addPageItem = (label, targetPage, disabled = false, active = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (!disabled) {
                    this.state.page = targetPage;
                    this.fetchAndRender();
                }
            });
            li.appendChild(a);
            this.pagination.appendChild(li);
        };

        addPageItem('«', page - 1, page <= 1);
        for (let p = Math.max(1, page - 2); p <= Math.min(totalPages, page + 2); p++) {
            addPageItem(String(p), p, false, p === page);
        }
        addPageItem('»', page + 1, page >= totalPages);
    }
}

export function initDataTables() {
    document.querySelectorAll('.datatable-component').forEach(root => new DataTable(root));
}
