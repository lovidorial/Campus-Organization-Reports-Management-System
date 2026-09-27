<x-app-layout>
    <div class="space-y-5">
        <header>
            <h1 class="text-2xl font-bold text-gray-900">OrgTrack FAQ</h1>
            <p class="mt-1 text-sm text-gray-500">Campus Organization Reports Management System</p>
        </header>

        @if($isAdmin)
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" x-data="{ query: '' }">
                <h2 class="text-lg font-bold text-gray-900">For Admins</h2>
                <div class="mt-3">
                    <label for="admin-faq-search" class="sr-only">Search admin FAQs</label>
                    <input id="admin-faq-search" type="text" x-model.debounce.150ms="query" placeholder="Search FAQs..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
                </div>
                <div class="mt-3 divide-y divide-gray-100">
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I review and approve or reject a GPOA or Activity Request?</summary>
                        <p class="mt-2 text-sm text-gray-600">Review GPOA submissions from <a class="text-amber-700 hover:underline" href="{{ route('admin.workflows.index') }}">GPOA Review</a>. Review activity requests in <a class="text-amber-700 hover:underline" href="{{ route('admin.activities') }}">Activity Monitoring</a>, where pending requests have approve and reject actions. Rejections should include clear feedback so the organization knows what to correct.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How does Activity Monitoring work, and what do its status filters mean?</summary>
                        <p class="mt-2 text-sm text-gray-600">Use the organization, category, search, and status filters to narrow activity requests. “All Status” includes every state; Pending means awaiting review; Approved, In Progress, Awaiting Report, and Report Submitted show the activity lifecycle; Closed means completed; Rejected means declined and awaiting resubmission where applicable.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I check an organization’s storage usage?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('admin.maintenance.index') }}">System Maintenance</a> to review database and storage usage, including organization-level usage and limits.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I view Activity Logs for auditing?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('admin.activity-logs.index') }}">Activity Logs</a> to search and filter recorded actions.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I generate Summary Reports across organizations?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('admin.summary-report') }}">Activity Overview Report</a> to review and export activity summary data across organizations.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I restore from a backup?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('admin.backups.index') }}">Backup &amp; Restore</a>, choose an available backup, and use its restore action. Confirm the selected backup and follow the page’s prompts before restoring.</p>
                    </details>
                </div>
            </section>
        @else
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" x-data="{ query: '' }">
                <h2 class="text-lg font-bold text-gray-900">For Users</h2>
                <div class="mt-3">
                    <label for="user-faq-search" class="sr-only">Search user FAQs</label>
                    <input id="user-faq-search" type="text" x-model.debounce.150ms="query" placeholder="Search FAQs..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
                </div>
                <div class="mt-3 divide-y divide-gray-100">
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">What is a GPOA, and why must it be approved before I submit activities?</summary>
                        <p class="mt-2 text-sm text-gray-600">A General Plan of Activities (GPOA) records your organization’s planned activities for the term. It must be approved first so activity requests can be tied to the organization’s approved plan.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I submit an Activity Request, and what is a Communication Letter?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('activity-requests.index') }}">Activity Requests</a>, create a request under an approved GPOA, and provide the venue, date, start and end time, category, description, objectives, expected outcome, target participants, person in charge, facilities/materials, and estimated budget. Submit requests at least 7 days before the activity. If the activity must be submitted sooner, mark it as <strong>Urgent</strong> and provide the required reason. A Communication Letter is the supporting document submitted with the request for review.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">What is a reservation slip, and when do I upload it?</summary>
                        <p class="mt-2 text-sm text-gray-600">The reservation slip documents the venue or facility reservation for an activity. Upload it using the file picker on the Activity Request’s row or detail page after the Communication Letter has been approved, when the request is eligible for the upload action. It is for the admin’s reference to confirm that the venue was secured.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">What does each activity status mean?</summary>
                        <p class="mt-2 text-sm text-gray-600"><strong class="text-amber-700">Pending</strong> means awaiting review. <strong class="text-green-700">Approved</strong> means the request was approved. <strong class="text-blue-700">In Progress</strong> means the activity has reached its scheduled date. <strong class="text-orange-700">Awaiting Report</strong> means the activity has taken place and its report is due. <strong class="text-indigo-700">Report Submitted</strong> means the report is awaiting review. <strong class="text-green-700">Closed</strong> means the activity workflow is complete. <strong class="text-red-700">Rejected</strong> means the request was declined; review its feedback for next steps.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">What is the Program Flow, and do I need to add one?</summary>
                        <p class="mt-2 text-sm text-gray-600">Program Flow is an optional schedule table attached to an Activity Request with columns for Time, Program, and Person in Charge. It is useful for events with a structured agenda. On the request form, you can freely add or remove rows.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">Does the system check if my chosen venue is available?</summary>
                        <p class="mt-2 text-sm text-gray-600">Yes. Venue availability is checked automatically against other approved or pending requests with overlapping dates and times. <strong>Available</strong> means there are no upcoming reservations, <strong>Scheduled</strong> means an approved activity is scheduled there, and <strong>Reserved</strong> means another pending or future request has reserved the venue.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I see all scheduled activities?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open the <a class="text-amber-700 hover:underline" href="{{ route('activities.calendar') }}">Activity Calendar</a> to view a month-by-month schedule of approved and upcoming activities.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I submit an Activity Report or Narrative Report?</summary>
                        <p class="mt-2 text-sm text-gray-600">After the activity is approved and conducted, open its Activity Request and choose the report submission action. Submit the required Narrative Report PDF, required acknowledgements, and any supporting photos.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">What is the Summary Report, and why is it locked?</summary>
                        <p class="mt-2 text-sm text-gray-600">The Summary Report summarizes your organization’s completed activities for the term. It becomes available after the GPOA is approved, at least one activity request exists, and every request under that GPOA has a submitted report or is closed.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I check my Submission History?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('submission-history') }}">Submission History</a> to see document versions, statuses, submission and approval dates, reviewers, and activity request totals.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I back up my organization’s data?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('my-backup.index') }}">My Data Backup</a> and follow the export options on the page.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">Who should I contact if my GPOA or Activity Request is rejected?</summary>
                        <p class="mt-2 text-sm text-gray-600">Read the reviewer’s feedback in the submission or request details and correct the listed issues before resubmitting. Contact your organization adviser or the campus office responsible for reviewing the submission if you need clarification.</p>
                    </details>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
