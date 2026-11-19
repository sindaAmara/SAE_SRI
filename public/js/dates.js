document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.querySelector('.datepicker');

    if (!dateInput || typeof flatpickr === 'undefined') {
        return;
    }

    flatpickr(dateInput, {
        dateFormat: 'Y-m-d',
        inline: true,
        defaultDate: dateInput.value || null,

        onChange: function (selectedDates, dateStr) {
            if (!dateStr) {
                return;
            }

            const url = new URL(window.location.href);

            url.searchParams.set('page', 'dates-admin');
            url.searchParams.set('action', 'view');
            url.searchParams.set('date', dateStr);

            window.location.href = url.toString();
        }
    });
});