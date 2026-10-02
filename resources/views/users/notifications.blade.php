<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-5">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Notifications</h1>
                <p class="mt-1 text-sm text-slate-600">Updates about your organization and activity reports.</p>
            </div>
            @if($notifications->contains(fn ($notification) => ! $notification->read_at))
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Mark all as read</button>
                </form>
            @endif
        </header>

        <section class="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white" aria-label="Notification list">
            @forelse($notifications as $notification)
                <article class="flex flex-wrap items-start justify-between gap-4 p-4 {{ $notification->read_at ? 'bg-white' : 'bg-sky-50' }}">
                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold text-slate-900">{{ $notification->title }}</h2>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $notification->message }}</p>
                        <time class="mt-2 block text-xs text-slate-500" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('M j, Y g:i A') }}</time>
                    </div>
                    @if(! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Mark as read</button>
                        </form>
                    @else
                        <span class="text-xs font-medium text-slate-500">Read</span>
                    @endif
                </article>
            @empty
                <p class="p-8 text-center text-sm text-slate-500">No notifications yet.</p>
            @endforelse
        </section>

        <div>{{ $notifications->links() }}</div>
    </div>
</x-app-layout>
