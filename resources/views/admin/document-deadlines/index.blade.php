<x-app-layout>
    <div class="space-y-5">
        <header>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-sky-700 hover:underline">&larr; Back to Dashboard</a>
            <h1 class="mt-3 text-xl font-bold text-gray-900">Document Deadlines</h1>
            <p class="mt-1 text-sm text-gray-500">Configure optional submission deadlines by document type, term, and school year.</p>
        </header>

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.document-deadlines.index') }}" class="grid gap-4 sm:grid-cols-3 sm:items-end">
                <div>
                    <label for="term" class="mb-1 block text-sm font-semibold text-gray-700">Term</label>
                    <select id="term" name="term" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach($terms->prepend($term)->unique() as $option)
                            <option value="{{ $option }}" @selected($option === $term)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="school_year" class="mb-1 block text-sm font-semibold text-gray-700">School year</label>
                    <select id="school_year" name="school_year" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach($schoolYears->prepend($schoolYear)->unique() as $option)
                            <option value="{{ $option }}" @selected($option === $schoolYear)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Load period</button>
            </form>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <form method="POST" action="{{ route('admin.document-deadlines.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="term" value="{{ $term }}">
                <input type="hidden" name="school_year" value="{{ $schoolYear }}">

                @php
                    $labels = [
                        'gpoa' => 'GPOA',
                        'activity_request' => 'Activity Request',
                        'summary_report' => 'Summary Report',
                        'activity_report' => 'Activity Report',
                    ];
                @endphp

                @foreach($labels as $type => $label)
                    <div class="grid gap-2 sm:grid-cols-[1fr_220px] sm:items-center">
                        <div>
                            <label for="deadline-{{ $type }}" class="font-semibold text-gray-800">{{ $label }}</label>
                            <p class="text-xs text-gray-500">Leave blank to disable this deadline.</p>
                        </div>
                        <input id="deadline-{{ $type }}" type="date" name="deadlines[{{ $type }}]" value="{{ $deadlines->get($type)?->deadline_date?->format('Y-m-d') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                @endforeach

                <button type="submit" class="rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Save deadlines</button>
            </form>
        </section>
    </div>
</x-app-layout>
