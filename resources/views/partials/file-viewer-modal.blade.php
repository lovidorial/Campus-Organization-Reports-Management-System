<div id="fileViewerModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="fileViewerTitle">
    <div class="flex h-full max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
        <header class="flex items-center gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
            <h2 id="fileViewerTitle" class="min-w-0 flex-1 truncate text-base font-semibold text-slate-900">Document</h2>
            <a id="fileViewerNewTab" href="#" target="_blank" rel="noopener" class="shrink-0 text-sm font-medium text-sky-700 underline">Open in new tab</a>
            <button type="button" data-file-viewer-close class="shrink-0 rounded border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Close</button>
        </header>
        <div class="relative min-h-0 flex-1 bg-slate-100">
            <div id="fileViewerLoading" class="absolute inset-0 z-10 grid place-items-center bg-white text-sm text-slate-500">Loading document…</div>
            <iframe id="fileViewerFrame" title="Document preview" src="about:blank" class="h-full w-full border-0"></iframe>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('fileViewerModal');
    const title = document.getElementById('fileViewerTitle');
    const frame = document.getElementById('fileViewerFrame');
    const loading = document.getElementById('fileViewerLoading');
    const newTab = document.getElementById('fileViewerNewTab');
    let previousFocus = null;
    let previousBodyOverflow = '';

    const closeModal = () => {
        if (modal.classList.contains('hidden')) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        frame.src = 'about:blank';
        loading.classList.remove('hidden');
        document.body.style.overflow = previousBodyOverflow;
        previousFocus?.focus();
    };

    document.addEventListener('click', event => {
        const link = event.target.closest('a[data-file-viewer]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.hasAttribute('download')) return;

        event.preventDefault();
        previousFocus = link;
        previousBodyOverflow = document.body.style.overflow;
        title.textContent = link.dataset.title || link.textContent.trim() || 'Document';
        newTab.href = link.href;
        frame.title = title.textContent;
        loading.classList.remove('hidden');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        frame.src = link.href;
    });

    frame.addEventListener('load', () => loading.classList.add('hidden'));

    modal.addEventListener('click', event => {
        if (event.target === modal || event.target.closest('[data-file-viewer-close]')) closeModal();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeModal();
    });
})();
</script>