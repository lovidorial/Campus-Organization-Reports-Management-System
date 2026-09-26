<x-app-layout>
    <div class="space-y-4">
        <header>
            <h1 class="text-xl font-bold text-gray-900">My Data Backup</h1>
            <p class="mt-1 text-sm text-gray-500">Export a private copy of your organization records and uploaded documents.</p>
        </header>

        @if($isNearStorageLimit)
            <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                Your organization is using {{ number_format($storageUsedMb, 1) }} MB of {{ number_format($storageLimitMb) }} MB ({{ $storageUsagePercent }}%). You are nearing the storage limit.
            </div>
        @endif

        <section class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Create an export</h2>
                <p class="mt-1 text-xs text-gray-500">Includes GPOAs, planned activities, activity requests, reports, photos, and available document files. Export archives are not counted toward your storage quota.</p>
            </div>
            <form method="POST" action="{{ route('my-backup.export') }}">
                @csrf
                <button type="submit" class="rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Generate backup</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white" aria-labelledby="available-backups-heading">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 id="available-backups-heading" class="text-sm font-semibold text-gray-900">Available exports</h2>
            </div>
            @if($backups->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-gray-500">You haven’t generated a backup yet.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach($backups as $backup)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $backup->created_at->format('M d, Y g:i A') }}</p>
                                <p class="text-xs text-gray-500">ZIP archive</p>
                            </div>
                            <a href="{{ route('my-backup.download', ['filename' => $backup->filename]) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Download</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-app-layout>