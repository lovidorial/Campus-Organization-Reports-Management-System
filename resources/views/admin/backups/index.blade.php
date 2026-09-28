<x-app-layout>
<div class="max-w-7xl mx-auto px-0 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm font-medium text-sky-700 hover:text-sky-900 mb-3">
                ← Back to Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Backup & Restore</h1>
            <p class="text-gray-600 mt-1">Create manual backups, restore from a prior archive, and configure automatic backup scheduling.</p>
        </div>
    </div>

    <div class="mb-6 rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950">
        <p class="font-semibold">Keep an off-server copy</p>
        <p class="mt-1">Download backups regularly and copy them to an external drive stored separately from this server. Backups remain local until you download them.</p>
    </div>

    @unless($archiveTrackingAvailable)
        <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
            Backup download tracking is not installed yet. Run <code>php artisan migrate --force</code> to enable per-archive download dates and backup status reporting.
        </div>
    @endunless

    @if($backupWarning)
        <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
            No successful backup has completed within the selected <strong>{{ str_replace('_', ' ', $backupSetting->frequency) }}</strong> frequency.
            @if($lastSuccessfulAt)
                Last success: {{ $lastSuccessfulAt->timezone(config('app.timezone'))->format('F j, Y g:i A') }}.
            @else
                No successful backup is recorded yet.
            @endif
        </div>
    @endif

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 text-sm">
        <p><strong>Last successful backup:</strong>
            {{ $lastSuccessfulAt ? $lastSuccessfulAt->timezone(config('app.timezone'))->format('F j, Y g:i A') : 'None recorded' }}
        </p>
        @if($backupSetting->last_error)
            <p class="mt-2 text-red-700"><strong>Last backup error:</strong> {{ $backupSetting->last_error }}</p>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Create Backup</h2>
            <form action="{{ route('admin.backups.store') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">
                    Create Manual Backup
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Automatic Schedule</h2>
            <form action="{{ route('admin.backups.schedule') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label for="frequency" class="block text-sm font-medium text-gray-700">Backup Frequency</label>
                        <select id="frequency" name="frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            <option value="manual" {{ $backupSetting->frequency === 'manual' ? 'selected' : '' }}>Manual only</option>
                            <option value="monthly" {{ $backupSetting->frequency === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="per_semester" {{ $backupSetting->frequency === 'per_semester' ? 'selected' : '' }}>Per semester</option>
                            <option value="per_school_year" {{ $backupSetting->frequency === 'per_school_year' ? 'selected' : '' }}>Per school year</option>
                        </select>
                    </div>

                    <div>
                        <label for="retention_count" class="block text-sm font-medium text-gray-700">Retention Count</label>
                        <input id="retention_count" type="number" min="1" max="100" value="{{ $backupSetting->retention_count }}" name="retention_count" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">
                        Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <section class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" aria-labelledby="restore-guide-heading">
        <h2 id="restore-guide-heading" class="font-semibold">Restore procedure</h2>
        <ol class="mt-2 list-decimal space-y-1 pl-5">
            <li>Download the archive and keep a separate copy before replacing the current data.</li>
            <li>Select Restore beside the chosen archive, type <strong>RESTORE</strong>, then confirm. This replaces the database and uploaded public files.</li>
            <li>After restoration, verify the organizations, latest submissions, and uploaded documents before reopening access.</li>
        </ol>
        <p class="mt-2 text-xs text-amber-900">Restore requires a ZIP containing <code>database.sql</code> and <code>storage/public/</code>, and may take several minutes. A local pre-restore recovery archive is created automatically. Do not interrupt the operation.</p>
    </section>

    <section class="mt-4 rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-700" aria-labelledby="scheduler-heading">
        <h2 id="scheduler-heading" class="font-semibold text-slate-900">Run the Laravel scheduler on this host</h2>
        <p class="mt-1">The scheduled backup command is registered with Laravel’s scheduler. Run this every minute from the project directory:</p>
        <pre class="mt-2 overflow-x-auto rounded bg-slate-900 p-3 text-xs text-white"><code>php artisan schedule:run</code></pre>
        <p class="mt-2 text-xs">Linux cron: <code>* * * * * cd /path/to/Orgtrack &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>. On Windows/XAMPP, create a Task Scheduler task that runs <code>php artisan schedule:run</code> every minute with the project directory as “Start in”.</p>
    </section>

    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-4 sm:p-6" aria-labelledby="upload-restore-heading">
        <h2 id="upload-restore-heading" class="text-lg font-semibold text-gray-900">Restore from a ZIP file</h2>
        <p class="mt-1 text-sm text-gray-600">The configured application limit is {{ number_format($maxRestoreUploadMb) }} MB. PHP limits: upload_max_filesize {{ $phpUploadLimit }}, post_max_size {{ $phpPostLimit }}.</p>
        <button type="button" data-open-upload-restore class="mt-3 inline-flex min-h-11 items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Choose ZIP to restore</button>
    </section>

    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Available Backups</h2>
        </div>

        @if($backups->isEmpty())
            <div class="p-6 text-sm text-gray-600">No backup archives yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">File</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Last Downloaded</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($backups as $backup)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $backup['name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $backup['created_at'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    @if(!$archiveTrackingAvailable)
                                        <span class="text-gray-500">Run migrations to enable tracking</span>
                                    @elseif($backup['last_downloaded_at'])
                                        {{ $backup['last_downloaded_at']->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                    @else
                                        <span class="font-semibold text-amber-700">Never downloaded</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex flex-wrap items-center justify-end gap-3">
                                    <a href="{{ route('admin.backups.download', ['filename' => $backup['name']]) }}" class="text-amber-700 hover:text-amber-900">Download</a>
                                    <button type="button" data-open-backup-restore data-filename="{{ $backup['name'] }}" data-date="{{ $backup['created_at'] }}" class="text-sky-700 hover:text-sky-900">Restore</button>
                                    <form action="{{ route('admin.backups.destroy', ['filename' => $backup['name']]) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this backup archive?')">Delete</button>
                                    </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div id="restoreConfirmModal" class="fixed inset-0 z-50 hidden bg-black/60 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="restore-modal-title">
    <div class="absolute left-1/2 top-1/2 max-h-[95vh] w-[calc(100%-24px)] max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl bg-white p-4 shadow-2xl sm:p-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 id="restore-modal-title" class="text-lg font-bold text-gray-900">Confirm backup restore</h2>
                <p class="mt-1 text-sm text-red-700">All data newer than this backup will be lost.</p>
            </div>
            <button type="button" data-close-restore-modal class="min-h-10 min-w-10 rounded border text-gray-600" aria-label="Close restore dialog">&times;</button>
        </div>
        <p class="mt-4 text-sm text-gray-700"><strong>Backup date:</strong> <span id="restoreBackupDate">Select a ZIP file.</span></p>
        <form action="{{ route('admin.backups.restore') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <input id="restoreBackupFilename" type="hidden" name="backup_filename" value="">
            <div>
                <label for="restoreBackupFile" class="block text-sm font-semibold text-gray-700">ZIP backup (optional when restoring a listed archive)</label>
                <input id="restoreBackupFile" type="file" name="backup_file" accept=".zip,application/zip" class="mt-1 block w-full rounded border border-gray-300 p-2 text-sm">
            </div>
            <div>
                <label for="restoreConfirmation" class="block text-sm font-semibold text-gray-700">Type RESTORE to continue</label>
                <input id="restoreConfirmation" type="text" name="confirmation" required autocomplete="off" class="mt-1 block min-h-11 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" data-close-restore-modal class="min-h-11 rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button>
                <button type="submit" class="min-h-11 rounded bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Restore and replace current data</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('restoreConfirmModal');
    const filename = document.getElementById('restoreBackupFilename');
    const date = document.getElementById('restoreBackupDate');
    const file = document.getElementById('restoreBackupFile');
    const confirmation = document.getElementById('restoreConfirmation');

    document.querySelectorAll('[data-open-backup-restore]').forEach((button) => {
        button.addEventListener('click', () => {
            filename.value = button.dataset.filename;
            date.textContent = button.dataset.date;
            file.value = '';
            modal.classList.remove('hidden');
            confirmation.focus();
        });
    });

    document.querySelector('[data-open-upload-restore]')?.addEventListener('click', () => {
        filename.value = '';
        date.textContent = 'Choose a ZIP file to display its date.';
        file.value = '';
        modal.classList.remove('hidden');
        file.focus();
    });

    file.addEventListener('change', () => {
        filename.value = '';
        const selected = file.files?.[0];
        date.textContent = selected ? new Date(selected.lastModified).toLocaleString() : 'Choose a ZIP file to display its date.';
    });

    document.querySelectorAll('[data-close-restore-modal]').forEach((button) => {
        button.addEventListener('click', () => modal.classList.add('hidden'));
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.classList.add('hidden');
    });
});
</script>
</x-app-layout>
