document.addEventListener('DOMContentLoaded', function() {
    // Initialize Flatpickr for all inputs with class "datepicker"
    flatpickr('.datepicker', {
        dateFormat: 'Y-m-d', // Match your PHP date format
        allowInput: true,    // Allow manual input
        clickOpens: true,    // Open on click
    });
});
