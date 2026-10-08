<div x-show="reviewOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center overflow-hidden bg-slate-950/60 p-2 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="import-review-title">
    <div class="mx-0 flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-none bg-white shadow-2xl sm:mx-2 sm:rounded-lg">
        <header class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 bg-white p-3">
            <div class="min-w-0">
                <h2 id="import-review-title" class="truncate whitespace-nowrap text-base font-bold text-slate-900">Review imported activities</h2>
                <p class="mt-0.5 text-sm text-slate-600"><span x-text="reviewFoundCount"></span> found, <span x-text="reviewImportCount"></span> will import, <span x-text="reviewAttentionCount"></span> need attention</p>
            </div>
            <button type="button" @click="cancelImportReview()" class="inline-flex min-h-9 min-w-9 shrink-0 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50" aria-label="Cancel import">&times;</button>
        </header>

        <div class="min-h-0 flex-1 space-y-3 overflow-auto p-3">
            <div x-show="reviewSkippedRows.length" x-data="{ showSkippedRows: false }" class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-950">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                    <p><span x-text="reviewSkippedRows.length"></span> rows beyond the 34 activity limit were not imported.</p>
                    <button type="button" @click="showSkippedRows = !showSkippedRows" class="shrink-0 font-semibold underline underline-offset-2" x-text="showSkippedRows ? 'Hide skipped rows' : 'Show skipped rows'"></button>
                </div>
                <ul x-show="showSkippedRows" x-cloak class="mt-2 max-h-32 list-disc space-y-0.5 overflow-y-auto border-t border-amber-200 pt-2 pl-5">
                    <template x-for="(title, index) in reviewSkippedRows" :key="index"><li x-text="title"></li></template>
                </ul>
            </div>
            <div x-show="reviewWarnings.some(warning => !warning.includes('additional activity rows were skipped because the maximum is 34'))" class="rounded-md bg-slate-50 p-3 text-xs text-slate-700">
                <template x-for="(warning, index) in reviewWarnings" :key="index">
                    <template x-if="!warning.includes('additional activity rows were skipped because the maximum is 34')"><p x-text="warning"></p></template>
                </template>
            </div>
            <p x-show="reviewApplyError" x-text="reviewApplyError" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert"></p>

            <div x-show="reviewHasExistingActivities" class="flex flex-wrap items-center gap-3 rounded-md border border-sky-200 bg-sky-50 p-3 text-sm text-sky-950">
                <span class="font-semibold">Activities are already entered. Choose how to continue:</span>
                <label class="inline-flex min-h-11 items-center gap-2"><input type="radio" value="replace" x-model="reviewMode"> Replace</label>
                <label class="inline-flex min-h-11 items-center gap-2"><input type="radio" value="append" x-model="reviewMode"> Append</label>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <label class="inline-flex min-h-9 items-center gap-2 text-xs font-medium text-slate-700">
                    <input type="checkbox" x-model="reviewNeedsOnly" class="rounded border-slate-300">
                    Needs attention only
                </label>
                <p class="text-xs text-slate-600"><span x-text="reviewAttentionCount"></span> need attention</p>
            </div>

            <div class="rounded-md border border-slate-200">
                <table class="w-full min-w-[700px] divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase text-slate-500">
                        <tr><th class="sticky top-0 z-10 w-10 min-w-10 bg-slate-50 px-2 py-1.5">#</th><th class="sticky top-0 z-10 bg-slate-50 px-2 py-1.5">Title</th><th class="sticky top-0 z-10 bg-slate-50 px-2 py-1.5">Date / time frame</th><th class="sticky top-0 z-10 bg-slate-50 px-2 py-1.5">Venue</th><th class="sticky top-0 z-10 w-10 min-w-10 bg-slate-50 px-2 py-1.5">SDGs</th><th class="sticky top-0 z-10 w-32 min-w-32 bg-slate-50 px-2 py-1.5">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(activity, index) in visibleReviewRows" :key="activity._key">
                            <tr>
                                <td colspan="6" class="p-0">
                                    <button type="button" @click="reviewExpandedIndex = reviewExpandedIndex === index ? null : index" class="grid min-h-9 w-full grid-cols-[2rem_minmax(10rem,1.4fr)_minmax(8rem,1fr)_minmax(8rem,1fr)_2rem_8rem] items-center gap-2 px-2 py-1.5 text-left hover:bg-slate-50" :aria-expanded="reviewExpandedIndex === index">
                                        <span class="tabular-nums text-slate-500" x-text="index + 1"></span>
                                        <span class="truncate whitespace-nowrap font-medium text-slate-900" :title="activity.title || 'Untitled activity'" x-text="activity.title || 'Untitled activity'"></span>
                                        <span class="truncate whitespace-nowrap text-slate-700" x-text="reviewDateLabel(activity)"></span>
                                        <span class="truncate text-slate-700" x-text="activity.venue || 'Venue missing'"></span>
                                        <span class="text-center tabular-nums text-slate-700" x-text="(activity.sdgs || []).length"></span>
                                        <span class="inline-flex w-32 items-center gap-1 justify-self-start rounded-full px-2 py-1 text-[10px] font-semibold" :class="reviewNeedsAttention(activity) ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"><span aria-hidden="true" x-text="reviewNeedsAttention(activity) ? '!' : '✓'"></span><span x-text="reviewNeedsAttention(activity) ? 'Needs attention' : 'OK'"></span></span>
                                    </button>
                                    <div x-show="reviewExpandedIndex === index" class="grid grid-cols-1 gap-3 border-t border-slate-100 bg-slate-50 p-3 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="sm:col-span-2 lg:col-span-3" x-show="activity.importWarnings.length">
                                            <p class="text-xs font-semibold text-amber-800">Parser warnings</p>
                                            <ul class="mt-1 list-disc pl-5 text-xs text-amber-800"><template x-for="(warning, warningIndex) in activity.importWarnings" :key="warningIndex"><li x-text="warning"></li></template></ul>
                                        </div>
                                        <label class="text-xs font-semibold text-slate-600">Title<input type="text" x-model="activity.title" class="mt-1 min-h-11 w-full rounded-md border border-slate-300 px-3 text-sm font-normal text-slate-900"></label>
                                        <label class="text-xs font-semibold text-slate-600">Date<input :type="activity.time_frame === 'month_only' ? 'month' : 'date'" x-model="activity.date" class="mt-1 min-h-11 w-full rounded-md border border-slate-300 px-3 text-sm font-normal text-slate-900"></label>
                                        <label class="text-xs font-semibold text-slate-600">Venue<input type="text" x-model="activity.venue" class="mt-1 min-h-11 w-full rounded-md border border-slate-300 px-3 text-sm font-normal text-slate-900"></label>
                                        <label class="text-xs font-semibold text-slate-600">Start time<input type="time" x-model="activity.start_time" class="mt-1 min-h-11 w-full rounded-md border border-slate-300 px-3 text-sm font-normal text-slate-900"></label>
                                        <label class="text-xs font-semibold text-slate-600">End time<input type="time" x-model="activity.end_time" class="mt-1 min-h-11 w-full rounded-md border border-slate-300 px-3 text-sm font-normal text-slate-900"></label>
                                        <p class="self-end text-xs text-slate-500">Other imported details are retained as parsed.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="visibleReviewRows.length === 0"><td colspan="6" class="px-2 py-6 text-center text-xs text-slate-500">No activities match this filter.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="flex shrink-0 flex-col gap-2 border-t border-slate-200 bg-white p-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <button type="button" @click="cancelImportReview()" class="inline-flex min-h-11 w-full items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">Cancel</button>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap">
                <button type="button" @click="confirmImportReview('ok')" class="inline-flex min-h-11 w-full items-center justify-center rounded-md border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 sm:w-auto">Import OK rows only</button>
                <button type="button" @click="confirmImportReview('all')" class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800 sm:w-auto">Import all</button>
            </div>
        </footer>
    </div>
</div>
