<x-app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm font-medium text-sky-700 hover:text-sky-900 mb-3">
                ← Back to Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Backup & Restore</h1>
            <p class="text-gray-600 mt-1">Create manual backups, restore from a prior archive, and configure automatic backup scheduling.</p>
        </div>
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
        <p class="mt-2 text-xs text-amber-900">Restore requires a ZIP containing <code>database.sql</code> and may take several minutes. Do not interrupt the operation.</p>
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
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($backups as $backup)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $backup['name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $backup['created_at'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-3">
                                    <a href="{{ route('admin.backups.download', ['filename' => $backup['name']]) }}" class="text-amber-700 hover:text-amber-900">Download</a>
                                    <form action="{{ route('admin.backups.restore') }}" method="POST" class="inline-flex items-center gap-1">
                                        @csrf
                                        <input type="hidden" name="backup_filename" value="{{ $backup['name'] }}">
                                        <input type="text" name="confirmation" value="" placeholder="Type RESTORE" required autocomplete="off" class="w-28 rounded border border-gray-300 px-2 py-1 text-xs" aria-label="Type RESTORE to confirm">
                                        <button type="submit" class="text-sky-700 hover:text-sky-900" onclick="return confirm('Restore this backup? This will overwrite the current database and uploaded files.')">Restore</button>
                                    </form>
                                    <form action="{{ route('admin.backups.destroy', ['filename' => $backup['name']]) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this backup archive?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
</x-app-layout>
