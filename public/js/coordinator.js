/**
 * Manages the coordinator page filters:
 * - Entrant/Sortant type toggle
 * - Zone filter conditional display
 * - Completion filter and search input
 * - Updates URL with filter parameters
 */
class CoordinatorManager {
    constructor() {
        // Detect language from DOM dataset, default to 'fr'
        this.lang = document.getElementById('app-config')?.dataset?.lang || 'fr';
        this.init();
    }

    /**
     * Initialize DOM elements and event listeners
     */
    init() {
        this.sortantCb = document.querySelector('input[name="entrant_sortant"][value="sortant"]');
        this.zoneGroup = document.getElementById('zone-filter-group');
        this.bindEvents();
    }

    /**
     * Bind all UI event listeners
     */
    bindEvents() {
        // ── Conditional zone filter for "sortant" ──
        if (this.sortantCb && this.zoneGroup) {
            this.sortantCb.addEventListener('change', () => {
                this.zoneGroup.style.display = this.sortantCb.checked ? '' : 'none';
                if (!this.sortantCb.checked) {
                    // Uncheck all zone checkboxes when disabled
                    this.zoneGroup.querySelectorAll('input[type=checkbox]')
                        .forEach(cb => cb.checked = false);
                }
                this.applyFilters();
            });
        }

        // ── Checkboxes trigger filter application ──
        document.querySelectorAll('.filters input[type=checkbox]')
            .forEach(cb => cb.addEventListener('change', () => this.applyFilters()));

        // Completion select filter
        const complet = document.getElementById('filter-complet');
        if (complet) complet.addEventListener('change', () => this.applyFilters());

        // ── Search functionality ──
        const btnSearch = document.getElementById('btn-search-loupe');
        if (btnSearch) btnSearch.addEventListener('click', () => this.applyFilters());

        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') this.applyFilters();
            });
        }
    }

    /**
     * Gather all active filters and redirect to the updated URL
     */
    applyFilters() {
        const params = new URLSearchParams({ page: 'coordinateur-etude', lang: this.lang });

        // Entrant/Sortant type
        const typeCb = document.querySelector('input[name="entrant_sortant"]:checked');
        if (typeCb) params.set('type', typeCb.value);

        // Zone (only if sortant is selected)
        if (this.sortantCb?.checked) {
            const zoneCb = document.querySelector('input[name="zone"]:checked');
            if (zoneCb) params.set('zone', zoneCb.value);
        }

        // Completion filter
        const complet = document.getElementById('filter-complet');
        if (complet && complet.value !== 'all') params.set('complet', complet.value);

        // Search input
        const search = document.getElementById('search');
        if (search?.value.trim()) params.set('search', search.value.trim());

        // Redirect to filtered page
        window.location.href = 'index.php?' + params.toString();
    }
}

// Initialize CoordinatorManager when DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => new CoordinatorManager());