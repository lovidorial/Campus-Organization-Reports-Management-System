<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CORMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 text-white transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-20 flex flex-col" style="background-color: #d97706;">
        <div class="p-5 border-b border-white/10">
            <h1 class="text-xl font-bold text-white">CSORMS  </h1>
            <p class="text-xs text-white-400 mt-0.5">Activity Tracking System</p>
        </div>
        <nav class="p-4 flex-1 overflow-y-auto">
            <ul class="space-y-1">

                @if(!auth()->user()->isAdmin())
                <!-- USER MENU -->
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('dashboard') ? '#e89600' : 'transparent' }};">
                         Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('gpoa.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('gpoa.*') ? '#e89600' : 'transparent' }};">
                         My GPOA
                    </a>
                </li>
                <li>
                    <a href="{{ route('workflow.communication-letter') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('workflow.communication-letter*') ? '#e89600' : 'transparent' }};">
                         Communication Letter
                    </a>
                </li>
                <li>
                    <a href="{{ route('workflow.summary-report') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('workflow.summary-report*') ? '#e89600' : 'transparent' }};">
                         Summary Report
                    </a>
                </li>
                <li>
                    <a href="{{ route('activity-requests.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('activity-requests.*') || request()->routeIs('activity-reports.*') ? '#e89600' : 'transparent' }};">
                         Activity Requests
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('profile.edit') ? '#e89600' : 'transparent' }};">
                         Edit Profile
                    </a>
                </li>

                @else
                <!-- ADMIN MENU -->
                <li class="pb-1">
                    <a href="{{ route('admin.dashboard') }}"
                       class="block px-4 py-2.5 rounded-lg transition font-bold text-white hover:bg-white/10"
                       style="background-color: {{ request()->routeIs('admin.dashboard') ? '#b45309' : 'transparent' }};">
                         Admin Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.workflows.index') }}"
                       class="block px-4 py-2.5 rounded-lg transition font-bold text-white hover:bg-white/10"
                       style="background-color: {{ request()->routeIs('admin.workflows.*') ? '#b45309' : 'transparent' }};">
                         GPOA Review
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.activities') }}"
                       class="block px-4 py-2.5 rounded-lg transition font-bold text-white hover:bg-white/10"
                       style="background-color: {{ request()->routeIs('admin.activities') ? '#b45309' : 'transparent' }};">
                         Activity Monitoring
                    </a>
                </li>
                <li class="mt-3">
                    <a href="{{ route('admin.organizations.index') }}"
                       class="block px-4 py-2.5 rounded-lg transition font-bold text-white hover:bg-white/10"
                       style="background-color: {{ request()->routeIs('admin.organizations.*') ? '#b45309' : 'transparent' }};">
                         Organization account
                    </a>
                </li>
                @endif

                <!-- Logout -->
                <li class="pt-4 mt-4 border-t border-white/10">
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="block px-4 py-2.5 rounded-lg transition font-bold text-white hover:bg-white/10 w-full text-left">
                         Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                </li>
            </ul>
        </nav>

        <!-- User info at bottom of sidebar -->
        <div class="p-4 border-t border-white/10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 flex-1">
                    @if(auth()->user()->profile_photo_path)
                        <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" class="w-9 h-9 rounded-full object-cover border-2 border-white/40"/>
                    @else
                        <img src="{{ asset('images/osdw.logo.jpg') }}" alt="OSDW Logo" class="w-9 h-9 rounded-full object-cover border-2 border-white/40" onerror="this.style.display='none'"/>
                    @endif
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-white/70 uppercase">{{ auth()->user()->role }}</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-white/80 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
                @if(!auth()->user()->isAdmin())
                <button type="button" @click="$dispatch('open-modal', 'notifications-modal')" class="relative ml-2 text-white focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if(auth()->user()->unreadNotificationsCount() > 0)
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ auth()->user()->unreadNotificationsCount() }}</span>
                    @endif
                </button>
                @endif
            </div>
        </div>
    </aside>

    @php
        $modalNotifications = auth()->user()?->notifications()->latest()->take(10)->get();
    @endphp

    <x-modal name="notifications-modal" maxWidth="lg">
        <div class="bg-white rounded-t-lg px-6 py-5 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Notifications</h2>
                <p class="text-sm text-gray-500">Recent updates on your workflow and submissions.</p>
            </div>
            <button type="button" @click="$dispatch('close-modal', 'notifications-modal')" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                <span class="sr-only">Close</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="px-6 py-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-600">Showing the latest notifications.</p>
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 font-semibold">Mark all as read</button>
                </form>
            </div>

            @if($modalNotifications->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-gray-500">
                    No notifications yet.
                </div>
            @else
                <div class="space-y-3">
                    @foreach($modalNotifications as $notification)
                        <div class="rounded-xl border p-4 flex items-start justify-between gap-4 {{ $notification->read_at ? 'border-gray-200 bg-white' : 'border-blue-300 bg-blue-50' }}">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800">{{ $notification->title }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
                                <p class="text-xs text-gray-400 mt-2">{{ $notification->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                            @if(!$notification->read_at)
                                <form action="{{ route('notifications.read', $notification) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs px-3 py-1 bg-blue-100 text-blue-700 rounded font-semibold">Mark read</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-modal>

    <!-- Mobile overlay -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Main Content -->
    <div class="flex-1 ml-0 md:ml-64 transition-all duration-300 overflow-auto">
        <!-- Top bar (mobile) -->
        <header class="bg-white shadow-sm h-14 flex items-center justify-between px-4 md:px-8 sticky top-0 z-10 md:hidden">
            <button id="sidebarToggle" class="p-2 focus:outline-none">
                <svg class="h-6 w-6 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="font-bold text-gray-800">CORMS</span>
            @if(auth()->user()->profile_photo_path)
                <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" class="w-9 h-9 rounded-full object-cover"/>
            @else
                <img src="{{ asset('images/osdw.logo.jpg') }}" alt="OSDW Logo" class="w-9 h-9 rounded-full object-cover" onerror="this.style.display='none'"/>
            @endif
        </header>

        <!-- Page Content -->
        <main class="p-6 md:p-8 max-w-7xl mx-auto w-full">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-5 flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" class="text-green-700 font-bold ml-4">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-5 flex items-center justify-between">
                    <span>{{ session('error') }}</span>
                    <button onclick="this.parentElement.remove()" class="text-red-700 font-bold ml-4">&times;</button>
                </div>
            @endif
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-5">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const toggle  = document.getElementById('sidebarToggle');
    const open  = () => { sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); };
    const close = () => { sidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); };
    toggle  && toggle.addEventListener('click', () => sidebar.classList.contains('-translate-x-full') ? open() : close());
    overlay && overlay.addEventListener('click', close);
});
</script>
@stack('scripts')
</body>
</html>
