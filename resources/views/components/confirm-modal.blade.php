<div id="orgConfirmModal" class="fixed inset-0 z-[70] hidden bg-black/60 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="orgConfirmTitle" aria-describedby="orgConfirmMessage">
    <div class="absolute left-1/2 top-1/2 max-h-[95vh] w-[calc(100%-24px)] max-w-md -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl bg-white p-5 shadow-2xl sm:p-6">
        <div class="flex items-start gap-3">
            <span id="orgConfirmIconWrap" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
                <svg data-confirm-icon="danger" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12m-10 0 .7 13h6.6L16 7M9 7V4h6v3m-5 4v5m4-5v5"/></svg>
                <svg data-confirm-icon="warning" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4m0 4h.01M10.3 3.9 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                <svg data-confirm-icon="primary" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <h2 id="orgConfirmTitle" class="text-lg font-bold text-slate-900"></h2>
                <p id="orgConfirmMessage" class="mt-1 whitespace-pre-line text-sm text-slate-600"></p>
            </div>
        </div>
        <div id="orgConfirmDetail" class="mt-4 hidden rounded-lg bg-slate-50 p-3">
            <p id="orgConfirmDetailLabel" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"></p>
            <p id="orgConfirmDetailValue" class="mt-1 break-all text-sm font-semibold text-slate-900"></p>
        </div>
        <div id="orgConfirmTypeWrap" class="mt-4 hidden">
            <label for="orgConfirmTypeInput" class="block text-xs font-semibold text-slate-700">Type <span id="orgConfirmTypeLabel"></span> to continue</label>
            <input id="orgConfirmTypeInput" type="text" autocomplete="off" class="mt-1 min-h-11 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
        </div>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button id="orgConfirmCancel" type="button" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
            <button id="orgConfirmAccept" type="button" class="inline-flex min-h-11 items-center justify-center rounded-md px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"></button>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('orgConfirmModal');
    if (!modal) return;

    const title = document.getElementById('orgConfirmTitle');
    const message = document.getElementById('orgConfirmMessage');
    const detail = document.getElementById('orgConfirmDetail');
    const detailLabel = document.getElementById('orgConfirmDetailLabel');
    const detailValue = document.getElementById('orgConfirmDetailValue');
    const typeWrap = document.getElementById('orgConfirmTypeWrap');
    const typeLabel = document.getElementById('orgConfirmTypeLabel');
    const typeInput = document.getElementById('orgConfirmTypeInput');
    const cancelButton = document.getElementById('orgConfirmCancel');
    const confirmButton = document.getElementById('orgConfirmAccept');
    const iconWrap = document.getElementById('orgConfirmIconWrap');
    let activeTrigger = null;
    let activeForm = null;
    let activeResolve = null;
    let requiredText = '';
    let confirmLabel = '';
    let confirmVariant = 'warning';

    const variantClasses = {
        danger: ['bg-red-100', 'text-red-700', 'bg-red-700', 'hover:bg-red-800'],
        warning: ['bg-amber-100', 'text-amber-700', 'bg-amber-600', 'hover:bg-amber-700'],
        primary: ['bg-emerald-100', 'text-emerald-700', 'bg-emerald-700', 'hover:bg-emerald-800'],
    };

    const close = (confirmed) => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        const resolve = activeResolve;
        activeResolve = null;
        if (activeForm && confirmed) {
            const form = activeForm;
            const submitter = activeTrigger;
            activeForm = null;
            if (requiredText) {
                form.querySelector('input[name="confirm_name"][data-org-confirm-generated]')?.remove();
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'confirm_name';
                hidden.value = requiredText;
                hidden.dataset.orgConfirmGenerated = 'true';
                form.append(hidden);
            }
            form.dataset.orgConfirmBypass = 'true';
            if (form.requestSubmit) form.requestSubmit(submitter instanceof HTMLButtonElement ? submitter : undefined);
            else form.submit();
        } else {
            activeForm = null;
        }
        if (activeTrigger instanceof HTMLElement) activeTrigger.focus();
        if (resolve) resolve(Boolean(confirmed));
    };

    const open = (options, trigger = document.activeElement, form = null) => new Promise((resolve) => {
        activeTrigger = trigger instanceof HTMLElement ? trigger : null;
        activeForm = form;
        activeResolve = resolve;
        requiredText = options.type || '';
        confirmLabel = options.label || 'Confirm';
        confirmVariant = variantClasses[options.variant] ? options.variant : 'warning';

        title.textContent = options.title || 'Are you sure?';
        message.textContent = options.message || '';
        detail.classList.toggle('hidden', !(options.detailLabel || options.detailValue));
        detailLabel.textContent = options.detailLabel || '';
        detailValue.textContent = options.detailValue || '';
        typeWrap.classList.toggle('hidden', !requiredText);
        typeLabel.textContent = requiredText;
        typeInput.value = '';
        confirmButton.disabled = Boolean(requiredText);
        confirmButton.textContent = confirmLabel;
        confirmButton.className = 'inline-flex min-h-11 items-center justify-center rounded-md px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60 ' + variantClasses[confirmVariant][2] + ' ' + variantClasses[confirmVariant][3];
        iconWrap.className = 'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full ' + variantClasses[confirmVariant][0] + ' ' + variantClasses[confirmVariant][1];
        modal.querySelectorAll('[data-confirm-icon]').forEach((icon) => icon.classList.toggle('hidden', icon.dataset.confirmIcon !== confirmVariant));
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        window.requestAnimationFrame(() => (confirmVariant === 'danger' ? cancelButton : confirmButton).focus());
    });

    window.orgConfirm = (options) => open(options);

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
        if (form.dataset.orgConfirmBypass === 'true') {
            delete form.dataset.orgConfirmBypass;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        const submitter = event.submitter instanceof HTMLElement ? event.submitter : document.activeElement;
        open({
            title: form.dataset.confirmTitle,
            message: form.dataset.confirmMessage,
            label: form.dataset.confirmLabel,
            variant: form.dataset.confirmVariant,
            detailLabel: form.dataset.confirmDetailLabel,
            detailValue: form.dataset.confirmDetailValue,
            type: form.dataset.confirmType,
        }, submitter, form);
    }, true);

    typeInput.addEventListener('input', () => {
        confirmButton.disabled = typeInput.value !== requiredText;
    });
    cancelButton.addEventListener('click', () => close(false));
    confirmButton.addEventListener('click', () => {
        if (requiredText && typeInput.value !== requiredText) return;
        confirmButton.disabled = true;
        confirmButton.textContent = 'Please wait…';
        close(true);
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close(false);
    });
    modal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close(false);
            return;
        }
        if (event.key !== 'Tab') return;
        const focusable = [...modal.querySelectorAll('button:not(:disabled), input:not(.hidden):not(:disabled)')]
            .filter((element) => !element.closest('.hidden'));
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
    window.addEventListener('pageshow', () => {
        confirmButton.disabled = false;
        confirmButton.textContent = confirmLabel || 'Confirm';
    });
})();
</script>
