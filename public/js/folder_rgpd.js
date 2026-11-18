document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('rgpd-modal');
    const section = document.querySelector('[data-first-deposit]');
    const infoButton = document.getElementById('rgpd-info-button');
    if (!modal || !section || !infoButton) return;

    let accepted = false;
    let previousFocus = null;
    let pendingInput = null;

    const openModal = (trigger, input = null) => {
        previousFocus = trigger || document.activeElement;
        pendingInput = input;
        modal.classList.add('active');
        modal.querySelector('[tabindex]')?.focus();
        (document.getElementById('rgpd-accept') || modal).focus();
    };

    const closeModal = (clearFile) => {
        modal.classList.remove('active');
        if (clearFile && pendingInput) pendingInput.value = '';
        pendingInput = null;
        if (previousFocus) previousFocus.focus();
    };

    infoButton.addEventListener('click', () => openModal(infoButton));
    document.getElementById('rgpd-accept')?.addEventListener('click', () => {
        accepted = true;
        closeModal(false);
    });
    document.getElementById('rgpd-cancel')?.addEventListener('click', () => closeModal(true));

    document.querySelectorAll('.piece-block__file-input').forEach((input) => {
        input.addEventListener('change', () => {
            if (input.files && input.files.length && section.dataset.firstDeposit === '1' && !accepted) {
                openModal(input, input);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('active')) closeModal(true);
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal(true);
    });
});
