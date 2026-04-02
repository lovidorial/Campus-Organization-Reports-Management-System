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
    <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 text-white transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-20 flex flex-col" style="background: linear-gradient(135deg, #f5a623 0%, #e89600 100%);">
        <div class="p-5 border-b" style="border-color: rgba(243, 239, 239, 0.2);">
            <h1 class="text-xl font-bold text-white">CORMS  </h1>
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
                    <a href="{{ route('gpoa.create') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('gpoa.create') ? '#e89600' : 'transparent' }};">
                         Submit Activity
                    </a>
                </li>
                <li>
                    <a href="{{ route('user.activities') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('user.activities') ? '#e89600' : 'transparent' }};">
                         My Activities
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
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('admin.dashboard') ? '#e89600' : 'transparent' }};">
                         Admin Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.activities') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('admin.activities') ? '#e89600' : 'transparent' }};">
                         Monitoring
                    </a>
                </li>
                <li class="mt-3">
                    <a href="{{ route('admin.organizations.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('admin.organizations.*') ? '#e89600' : 'transparent' }};">
                         Organizations
                    </a>
                </li>
                <li class="mt-3">
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('admin.users.*') ? '#e89600' : 'transparent' }};">
                         Manage Users
                    </a>
                </li>
                @endif

                <!-- Logout -->
                <li class="pt-4 mt-4 border-t border-slate-700">
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm text-white-300 hover:bg-red-600 hover:text-white w-full">
                         Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                </li>
            </ul>
        </nav>

        <!-- User info at bottom of sidebar -->
        <div class="p-4 border-t border-slate-700">
            <div class="flex items-center gap-3">
                @if(auth()->user()->profile_photo_path)
                    <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" class="w-9 h-9 rounded-full object-cover"/>
                @else
                    <img src="{{ asset('images/osdw.logo.jpg') }}" alt="OSDW Logo" class="w-9 h-9 rounded-full object-cover" onerror="this.style.display='none'"/>
                @endif
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400 uppercase">{{ auth()->user()->role }}</p>
                </div>
            </div>
        </div>
    </aside>

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
