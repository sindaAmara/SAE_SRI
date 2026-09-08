/**
 * Manages dashboard UI interactions
 * - Currently supports accordion toggle for sections
 */
class DashboardManager {
    constructor() {
        // Additional initializations can be added here if the dashboard grows
        console.log("DashboardManager initialized");
    }

    /**
     * Toggle a dropdown/accordion section
     * @param {string} section - The section name (e.g., 'sortants', 'entrants')
     */
    toggleAccordion(section) {
        const content = document.getElementById('contenu-' + section);
        const arrow   = document.getElementById('fleche-' + section);

        // Safety check in case elements do not exist
        if (!content || !arrow) return;

        const isShown = content.classList.contains('afficher');

        if (isShown) {
            content.classList.remove('afficher');
            arrow.classList.remove('ouverte');
        } else {
            content.classList.add('afficher');
            arrow.classList.add('ouverte');
        }
    }
}

// Make a global instance accessible in the window
window.dashboardManager = new DashboardManager();