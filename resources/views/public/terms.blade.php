<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-8 py-12">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">CSORMS</p>
            <h1 class="text-3xl font-bold text-slate-900">Terms and Conditions</h1>

            <div class="mt-8 space-y-6 text-sm leading-7 text-slate-700">
                <p>
                    By accessing and using the Campus Organization Reports Management System (CSORMS),
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
                        <li>You are responsible for ensuring all information submitted in GPOA forms, activity requests, narrative reports, and related documents is accurate, complete, and up to date.</li>
                        <li>Any incorrect, incomplete, or misleading information may delay review, trigger corrective actions, or affect approval outcomes.</li>
                        <li>Submission of false or misleading data is strictly prohibited and may result in administrative action.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Account and Credential Responsibility</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-6">
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
