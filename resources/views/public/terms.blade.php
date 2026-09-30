<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-8 py-12">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">OrgTrack (Campus Organization Reports Management System)</p>
            <h1 class="text-3xl font-bold text-slate-900">Terms and Conditions</h1>

            <div class="mt-8 space-y-6 text-sm leading-7 text-slate-700">
                <p>
                    By accessing and using OrgTrack (Campus Organization Reports Management System),
                    you agree to use the platform responsibly and only for authorized campus organization
                    operations, reporting, and communication activities.
                </p>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Acceptable Use</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-6">
                        <li>Use the system only for legitimate student organization functions, activities, and reporting requirements.</li>
                        <li>Do not upload, request, or share content that is unlawful, harmful, offensive, or contrary to University policies.</li>
                        <li>Protect the confidentiality of organization records and do not misuse the platform to alter or interfere with another account's data.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Accuracy of Submitted Information</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-6">
                        <li>You are responsible for ensuring GPOA activities, communication letters, narrative reports, and related information are accurate, complete, and up to date.</li>
                        <li>Inaccurate or late submissions can change an activity's monitoring status (Pending, Ongoing, Completed, or Late) and may be returned for revision.</li>
                        <li>Submitting false or misleading information is prohibited and may lead to administrative action.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Communication Letters and Narrative Reports</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-6">
                        <li>Uploaded communication letters must already be signed by the responsible signatories. You confirm this when uploading; only PDF files are accepted for letters.</li>
                        <li>Narrative reports must accurately describe activities that actually took place. When submitting, you confirm the report is accurate and that its required signatories have signed it.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Monitoring and Records</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-6">
                        <li>Administrators can view submitted documents, record monitoring results, and view progress reports.</li>
                        <li>Activity progress and compliance status are visible to authorized administrators.</li>
                        <li>Organization records may be included in system backups and summary reports.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Uploaded Files and Storage</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-6">
                        <li>Uploaded files count against your organization's storage limit.</li>
                        <li>Do not upload files unrelated to organization activities or files containing another person's sensitive personal information without their permission.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Account and Credential Responsibility</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-6">
                        <li>Organization accounts are created and managed by administrators. Officers must not share credentials or create accounts on their own.</li>
                        <li>You are responsible for maintaining the confidentiality of your account credentials and for all actions taken using your account.</li>
                        <li>You must immediately inform the Office of Student Development and Welfare (OSDW) if you suspect unauthorized access or misuse of your account.</li>
                        <li>Only authorized officers or designated representatives may use organization accounts for official submissions and updates.</li>
                    </ul>
                </div>

                <p>
                    By accepting these Terms and Conditions, you acknowledge that you have read, understood,
                    and agree to comply with the responsibilities outlined above.
                </p>
            </div>

            <form method="POST" action="{{ route('terms.accept.store') }}" class="mt-10">
                @csrf

                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700">
                    I Accept
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
