<x-app-layout>
    <div class="space-y-5">
        <header>
            <h1 class="text-xl font-bold text-gray-900">System Maintenance</h1>
            <p class="mt-1 text-sm text-gray-500">Current database and application storage footprint.</p>
        </header>

        <section aria-label="System storage totals" class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase text-gray-500">Database size</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $database_bytes === null ? 'Unavailable' : number_format($database_bytes / 1048576, 2) . ' MB' }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase text-gray-500">Storage used (storage/app)</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($storage_bytes / 1048576, 2) }} MB</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white" aria-labelledby="organization-storage-heading">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 id="organization-storage-heading" class="text-sm font-semibold text-gray-900">Organization storage</h2>
                <p class="mt-1 text-xs text-gray-500">Sum of existing files referenced by each organization’s accounts and submissions. Unreferenced files and shared backups are included in the total above, not assigned to an organization.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                        <tr><th scope="col" class="px-4 py-2.5">Organization</th><th scope="col" class="px-4 py-2.5 text-right">Members</th><th scope="col" class="px-4 py-2.5 text-right">Used</th><th scope="col" class="px-4 py-2.5 text-right">Limit</th><th scope="col" class="px-4 py-2.5">Usage</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($organizations as $organization)
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-gray-900">{{ $organization['name'] }}</td>
                                <td class="px-4 py-2.5 text-right text-gray-600">{{ $organization['members'] }}</td>
                                <td class="px-4 py-2.5 text-right text-gray-900">{{ number_format($organization['bytes'] / 1048576, 2) }} MB</td>
                                <td class="px-4 py-2.5 text-right text-gray-600">{{ number_format($organization['limit_mb']) }} MB</td>
                                <td class="px-4 py-2.5">
                                    @php $usagePercent = min(100, (int) round($organization['bytes'] / max(1, $organization['limit_mb'] * 1048576) * 100)); @endphp
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-100"><div class="h-full {{ $usagePercent >= 90 ? 'bg-red-500' : ($usagePercent >= 75 ? 'bg-amber-500' : 'bg-green-600') }}" style="width: {{ $usagePercent }}%"></div></div>
                                        <span class="text-xs text-gray-600">{{ $usagePercent }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No organizations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>