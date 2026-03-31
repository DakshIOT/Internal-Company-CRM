import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');

    if (!submitter) {
        return;
    }

    form.dataset.submitting = 'true';

    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
        button.disabled = true;
    });

    if (submitter instanceof HTMLButtonElement) {
        submitter.classList.add('is-loading');

        const loadingLabel = submitter.getAttribute('data-loading-label') || submitter.textContent.trim() || 'Working...';

        if (!submitter.dataset.originalHtml) {
            submitter.dataset.originalHtml = submitter.innerHTML;
        }

        submitter.innerHTML = `
            <span class="crm-button-loader" aria-hidden="true"></span>
            <span>${loadingLabel}</span>
        `;
    }

    if (submitter instanceof HTMLInputElement) {
        submitter.dataset.originalValue = submitter.value;
        submitter.value = submitter.getAttribute('data-loading-label') || 'Working...';
    }
});
