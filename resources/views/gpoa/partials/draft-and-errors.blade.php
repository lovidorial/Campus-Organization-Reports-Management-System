<div x-show="hasDraft" x-cloak class="mb-4 flex flex-col gap-3 rounded-md border border-sky-200 bg-sky-50 p-3 text-sm text-sky-950 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p>Resume your draft (<span class="text-sky-800" x-text="draftSavedLabel"></span>)</p>
        <p class="mt-1 text-xs text-slate-700">Your uploaded PDF can't be restored. Please choose it again.</p>
    </div>
    <div class="flex gap-2">
        <button type="button" @click="resumeDraft()" class="inline-flex min-h-11 items-center justify-center rounded-md bg-sky-700 px-3 py-2 font-semibold text-white hover:bg-sky-800">Resume</button>
        <button type="button" @click="discardConfirmOpen = true" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-500 bg-white px-3 py-2 font-semibold text-slate-900 hover:border-red-700 hover:bg-red-50 hover:text-red-800">Discard</button>
    </div>
</div>

<p x-show="pdfMustReupload" x-cloak class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">Your uploaded PDF can't be restored. Please choose it again.</p>

<p x-show="draftTooLarge" x-cloak class="mb-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" role="status">Draft too large to save automatically. Please submit or keep this page open.</p>
<p x-show="draftSaveError" x-cloak x-text="draftSaveError" class="mb-2 text-sm text-red-700" role="alert"></p>

<div x-show="discardConfirmOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="discard-draft-title">
    <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
        <h2 id="discard-draft-title" class="text-lg font-bold text-slate-900">Discard this draft?</h2>
        <p class="mt-2 text-sm text-slate-700">This will permanently delete the saved entries.</p>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" @click="cancelDiscardDraft()" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Cancel</button>
            <button type="button" @click="discardDraft()" class="inline-flex min-h-11 items-center justify-center rounded-md border border-red-700 bg-white px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-50">Discard</button>
        </div>
    </div>
</div>

<div x-show="errorSummaryOpen && activityErrorItems.length" x-cloak class="mb-4 rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-950" role="alert" aria-live="polite">
    <p class="font-bold"><span x-text="errorActivityCount"></span> activities need fixes</p>
    <ul class="mt-2 list-disc space-y-1 pl-5">
        <template x-for="(item, itemIndex) in activityErrorItems" :key="`${item.index}-${item.field}-${itemIndex}`">
            <li><button type="button" @click="focusActivityError(item)" class="text-left underline decoration-red-400 underline-offset-2 hover:text-red-700" x-text="item.message"></button></li>
        </template>
    </ul>
</div>
