/**
 * Class representing partner institution management logic.
 * Handles displaying and hiding the add-partner form.
 */
class PartnerManager {
    constructor() {
        // Button to open the "Add Partner" form
        this.addPartnerBtn = document.querySelector('.btn-add-partner');

        // Container of the partner creation form
        this.partnerForm = document.getElementById('partner-form-container');

        // Button to cancel and hide the form
        this.cancelBtn = document.querySelector('.btn-cancel');

        // Initialize event listeners
        this.initEvents();
    }

    /**
     * Attaches click events for showing/hiding the partner form.
     */
    initEvents() {
        if (this.addPartnerBtn && this.partnerForm && this.cancelBtn) {
            // Show form when "Add Partner" button is clicked
            this.addPartnerBtn.addEventListener('click', () => this.showForm());

            // Hide form when "Cancel" button is clicked
            this.cancelBtn.addEventListener('click', () => this.hideForm());
        }
    }

    /**
     * Displays the partner creation form and hides the "Add" button.
     */
    showForm() {
        this.addPartnerBtn.style.display = 'none';
        this.partnerForm.classList.remove('hidden');
    }

    /**
     * Hides the partner creation form, resets its inputs, and shows the "Add" button.
     */
    hideForm() {
        this.partnerForm.classList.add('hidden');
        this.addPartnerBtn.style.display = 'inline-flex';

        // Reset form inputs
        const form = this.partnerForm.querySelector('form');
        if (form) form.reset();
    }
}

// Instantiate the manager globally after DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.partnerManager = new PartnerManager();
});