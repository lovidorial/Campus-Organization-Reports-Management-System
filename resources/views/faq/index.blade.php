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
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I monitor GPOA progress per organization?</summary>
                        <p class="mt-2 text-sm text-gray-600">Use the <a class="text-amber-700 hover:underline" href="{{ route('admin.dashboard') }}">Monitoring Dashboard</a> to compare planned activities and completion progress by organization. Open <a class="text-amber-700 hover:underline" href="{{ route('admin.gpoa.index') }}">GPOA Monitoring</a> to find an organization’s submitted plans and their activities.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How does Activity Monitoring work, and what do the status filters mean?</summary>
                        <p class="mt-2 text-sm text-gray-600">Each planned activity is tracked by its signed communication letter and narrative report. Pending means the letter is missing or its request was rejected; Ongoing means the letter is present but the narrative report is missing, awaiting review, or needs revision; Completed means the report has been approved. Late: Narrative report missing after the deadline. Filter by organization, category, college, term, school year, search text, or status (including Late).</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I review a communication letter or narrative report?</summary>
                        <p class="mt-2 text-sm text-gray-600">In <a class="text-amber-700 hover:underline" href="{{ route('admin.activities') }}">Activity Monitoring</a>, open the narrative report, photos, and attendance sheet as needed, then approve the report or request a revision with feedback.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I record a monitoring result?</summary>
                        <p class="mt-2 text-sm text-gray-600">In the activity row in <a class="text-amber-700 hover:underline" href="{{ route('admin.activities') }}">Activity Monitoring</a>, choose Add remark or Edit remark, select Aligned, Partially Aligned, or Not Aligned, and optionally add notes. Saving creates or updates one monitoring record for that planned activity; it does not change the activity status.</p>
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
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('admin.summary-report') }}">Activity Overview Report</a> to review cross-organization activity summaries. Filter by term, organization, category, or date range, then export a PDF or spreadsheet.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I restore from a backup?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('admin.backups.index') }}">Backup &amp; Restore</a>, choose an existing archive or upload a ZIP, and type RESTORE to confirm. The system creates a pre-restore backup before applying it.</p>
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
                        <summary class="cursor-pointer font-semibold text-gray-800">How does GPOA monitoring work?</summary>
                        <p class="mt-2 text-sm text-gray-600">The system tracks every planned activity under your approved GPOA as Activity #1, #2, #3, and so on, ordered by date. Each activity has two requirements: a signed communication letter and a narrative report. The <a class="text-amber-700 hover:underline" href="{{ route('activity-monitor.index') }}">Activity Monitor</a> shows each activity’s status and an overall progress bar.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">What do Pending, Ongoing and Completed mean?</summary>
                        <p class="mt-2 text-sm text-gray-600"><strong class="text-amber-700">Pending</strong> means the signed communication letter has not been uploaded, or the activity request was rejected and the letter must be submitted again. <strong class="text-sky-700">Ongoing</strong> means the letter is uploaded but the narrative report is missing or was returned for revision. <strong class="text-green-700">Completed</strong> means both requirements are submitted. The <strong class="text-red-700">Late</strong> flag means: Narrative report missing after the deadline.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How is my overall GPOA progress calculated?</summary>
                        <p class="mt-2 text-sm text-gray-600">Progress is Completed activities divided by total planned activities. The <a class="text-amber-700 hover:underline" href="{{ route('activity-monitor.index') }}">Activity Monitor</a> shows the percentage and counts of Completed, Ongoing, and Pending activities.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I submit a communication letter?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open the activity and upload the signed letter as a PDF, then tick the confirmation that it is signed. Creating the letter inside the system is not available.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I submit the narrative report?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open the activity and either upload a PDF or write the report in the system to generate a PDF. Supporting photos are optional. If a Needs Revision notice and feedback appear, read the feedback, update the report, and save it again.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">What is a GPOA, and how do I submit or update it?</summary>
                        <p class="mt-2 text-sm text-gray-600">A General Plan of Action (GPOA) records your organization’s planned activities for a term and school year. After submission, a GPOA is locked. Finish all activities in the previous GPOA before submitting another plan. Contact your administrator if you need to request changes.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I submit an Activity Request?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('activity-requests.create') }}">Request Activity</a>, choose a planned activity in your GPOA, and enter its details, date, times, venue, and required planning information. Upload the communication letter separately from the activity details.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">What is Program Flow, and does it use a time picker?</summary>
                        <p class="mt-2 text-sm text-gray-600">Program Flow is an optional schedule with a time, program item, and person in charge for each row. Enter row times in its Time field; the separate activity start and end time fields use your browser’s time picker. You can add or remove rows.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">Does the system check if my chosen venue is available?</summary>
                        <p class="mt-2 text-sm text-gray-600">Yes. The system checks other non-cancelled requests for overlapping dates and times at that venue and blocks a conflict. Venue labels are <strong>Available</strong> when there is no reservation, <strong>Reserved</strong> for a future reservation, and <strong>Scheduled</strong> for an activity happening now.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I see activities on the calendar?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open the <a class="text-amber-700 hover:underline" href="{{ route('activities.calendar') }}">Activity Calendar</a> to view your organization’s activities by month and optionally filter by venue. Calendar events show their current monitoring status.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">Can I submit a Summary Report?</summary>
                        <p class="mt-2 text-sm text-gray-600">There is no user Summary Report page in the current system. The Activity Overview Report is an admin report; use Activity Monitor to check your own planned-activity progress.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">How do I back up my organization’s data?</summary>
                        <p class="mt-2 text-sm text-gray-600">Open <a class="text-amber-700 hover:underline" href="{{ route('my-backup.index') }}">My Data Backup</a> and generate an export. When it is ready, download it from your backup list.</p>
                    </details>
                    <details class="py-3" x-show="!query || $el.querySelector('summary').textContent.toLowerCase().includes(query.toLowerCase())">
                        <summary class="cursor-pointer font-semibold text-gray-800">What should I do if my GPOA or Activity Request is rejected?</summary>
                        <p class="mt-2 text-sm text-gray-600">Read the rejection reason shown in the GPOA or request details and contact your adviser or the reviewing campus office if you need clarification. For an activity request, upload a corrected signed letter or resubmit the narrative report as needed.</p>
                    </details>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
