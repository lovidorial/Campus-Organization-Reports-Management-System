<x-app-layout>
<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Notifications</h2>
        <p class="text-sm text-gray-500">Updates about your document submissions and workflow progress</p>
    </div>
    <form action="{{ route('notifications.read-all') }}" method="POST">
        @csrf
        <button type="submit" class="text-sm px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 font-semibold">Mark all as read</button>
    </form>
</div>

<div class="space-y-3">
    @forelse($notifications as $notification)
    <div class="bg-white rounded-xl border p-4 flex items-start justify-between gap-4 {{ $notification->read_at ? '' : 'border-blue-300 bg-blue-50' }}">
        <div class="flex-1">
            <p class="font-semibold text-gray-800">{{ $notification->title }}</p>
            <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ $notification->created_at->format('M d, Y h:i A') }}</p>
        </div>
        @if(!$notification->read_at)
        <form action="{{ route('notifications.read', $notification) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="text-xs px-3 py-1 bg-blue-100 text-blue-700 rounded font-semibold">Mark read</button>
        </form>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-xl border p-10 text-center text-gray-400">
        No notifications yet.
    </div>
    @endforelse
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
</x-app-layout>
