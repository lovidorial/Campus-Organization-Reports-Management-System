<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OrgTrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #sidebar { background: radial-gradient(circle at 100% 0%, #E65100 0%, rgba(230,81,0,0) 60%), linear-gradient(180deg, #3A1A06 0%, #2A1204 100%) !important; color: #FFFFFF; }
        #sidebar > div:first-child, #sidebar > div:last-child, #sidebar [class*="border-"] { border-color: rgba(255, 255, 255, 0.12) !important; }
        #sidebar > div:first-child h1 { color: #FFFFFF !important; }
        #sidebar > div:first-child p { color: rgba(255, 255, 255, 0.75) !important; }
        #sidebar nav p { color: rgba(255, 255, 255, 0.60) !important; }
        #sidebar nav { background-color: transparent !important; color: #FFFFFF; text-shadow: none !important; scrollbar-color: rgba(255, 255, 255, 0.25) transparent; }
        #sidebar nav::-webkit-scrollbar-track { background: transparent; }
        #sidebar nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.25); border-radius: 9999px; }
        #sidebar nav a, #sidebar nav button { color: #FFFFFF !important; background-color: transparent !important; text-shadow: none !important; }
        #sidebar nav a:hover, #sidebar nav button:hover { color: #FFFFFF !important; background-color: rgba(255, 255, 255, 0.08) !important; }
        #sidebar nav svg { color: rgba(255, 255, 255, 0.90); }
        #sidebar nav button[aria-expanded="true"] { color: #FFFFFF !important; background-color: rgba(255, 255, 255, 0.06) !important; }
        #sidebar nav button[aria-expanded="true"] svg:last-child { color: #FFB74D; }
        #sidebar > div:last-child p:first-child { color: #FFFFFF !important; }
        #sidebar > div:last-child p:last-child { color: rgba(255, 255, 255, 0.65) !important; }
        #sidebar > div:last-child button { color: #FFFFFF; }
        #sidebar > div:last-child button:hover, #sidebar > div:last-child a:hover { background-color: rgba(255, 255, 255, 0.08) !important; color: #FFB74D !important; }
        #sidebar > div:last-child a { color: #FFFFFF !important; }
        #sidebar > div:last-child [class*="ring-"] { --tw-ring-color: rgba(255, 255, 255, 0.12) !important; }
        #sidebar > div:last-child [class*="border-"] { border-color: rgba(255, 255, 255, 0.12) !important; }
        #sidebar > div:last-child svg { color: #FFFFFF; }
        #sidebar > div:last-child > div { background-color: rgba(0, 0, 0, 0.15); }
        #sidebar nav li > a[onclick]:hover, #sidebar > div:last-child div[x-show] a[onclick]:hover { color: #FFB74D !important; background-color: rgba(255, 255, 255, 0.08) !important; }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex min-h-screen">

    <!-- Sidebar -->
    <div
        data-unread-count="{{ auth()->user()->unreadNotificationsCount() }}"
        x-data="{
            notificationsOpen: false,
            unreadCount: Number($el.dataset.unreadCount || 0),
            fetchUnreadCount() {
                fetch('{{ route('notifications.unread-count') }}', {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                    },
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Unable to fetch unread notification count.');
                        }

                        return response.json();
                    })
                    .then(data => {
                        this.unreadCount = Number(data.count) || 0;
                    })
                    .catch(() => {});
            },
            init() {
                if (this.$el._unreadCountInterval) {
                    clearInterval(this.$el._unreadCountInterval);
                }

                this.fetchUnreadCount();
                this.unreadCountInterval = setInterval(() => this.fetchUnreadCount(), 15000);
                this.$el._unreadCountInterval = this.unreadCountInterval;
            },
            destroy() {
                if (this.unreadCountInterval) {
                    clearInterval(this.unreadCountInterval);
                }

                this.$el._unreadCountInterval = null;
            }
        }"
    >
    <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 text-white transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-20 flex flex-col" @style(['--sidebar-accent' => '#FFB74D', 'background' => 'radial-gradient(circle at 100% 0%, #E65100 0%, rgba(230,81,0,0) 60%), linear-gradient(180deg, #3A1A06 0%, #2A1204 100%)'])>
        <div class="p-5 border-b border-white/10">
            <div class="flex items-center justify-between gap-3">
                <img src="{{ asset('images/orgTracklogo.png') }}" alt="OrgTrack logo" class="h-10 w-10 object-contain rounded-md">
                <div>
                    <h1 class="text-xl font-bold text-white">OrgTrack</h1>
                    <p class="text-xs text-white/75 mt-0.5">Student Organization Reports</p>
                </div>
                <button type="button" data-sidebar-close class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white hover:bg-white/10 md:hidden" aria-label="Close navigation menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
        </div>
        <nav class="p-4 flex-1 overflow-y-auto bg-transparent">
            <ul class="space-y-1">

                @if(!auth()->user()->isAdmin())
                <!-- USER MENU -->
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">
                         Dashboard
                    </a>
                </li>
                <li x-data="{ open: {{ request()->routeIs('gpoa.*') || request()->routeIs('activities.calendar') || request()->routeIs('activity-monitor.*') || request()->routeIs('activity-requests.*') ? 'true' : 'false' }} }">
                    <button type="button"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            class="flex items-center justify-between gap-3 w-full px-4 py-2.5 rounded-lg transition font-bold text-white"
                            :style="open || {{ request()->routeIs('gpoa.*') || request()->routeIs('activities.calendar') || request()->routeIs('activity-monitor.*') || request()->routeIs('activity-requests.*') ? 'true' : 'false' }} ? 'background-color: rgba(233,99,26,0.10);' : 'background-color: transparent;'">
                        <span>Documents & Activities</span>
                        <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" :class="open ? 'rotate-90' : ''">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 max-h-0"
                         x-transition:enter-end="opacity-100 max-h-40"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 max-h-40"
                         x-transition:leave-end="opacity-0 max-h-0"
                         class="overflow-hidden space-y-1 mt-1 pl-4">
                        <a href="{{ route('gpoa.index') }}"
                           class="block px-4 py-2 rounded-lg transition font-bold text-white {{ request()->routeIs('gpoa.*') ? 'sidebar-link-active' : '' }}">
                            My GPOA
                        </a>
                                <a href="{{ route('activity-monitor.index') }}"
                           class="block px-4 py-2 rounded-lg transition font-bold text-white {{ request()->routeIs('activity-monitor.*') || request()->routeIs('activity-requests.*') ? 'sidebar-link-active' : '' }}">
                                     Activity Monitor
                        </a>
                        <a href="{{ route('activities.calendar') }}"
                           class="block px-4 py-2 rounded-lg transition font-bold text-white {{ request()->routeIs('activities.calendar') ? 'sidebar-link-active' : '' }}">
                            Activity Calendar
                        </a>
                    </div>
                </li>
                <li>
                    <a href="{{ route('organization.officers.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white {{ request()->routeIs('organization.officers.*') ? 'sidebar-link-active' : '' }}">
                         Officer Archive
                    </a>
                </li>
                <li>
                    <a href="{{ route('organization.members.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white {{ request()->routeIs('organization.members.*') ? 'sidebar-link-active' : '' }}">
                         Org Chart
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white {{ request()->routeIs('profile.edit') ? 'sidebar-link-active' : '' }}">
                         Edit Profile
                    </a>
                </li>
                <li>
                    <a href="{{ route('my-backup.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white {{ request()->routeIs('my-backup.*') ? 'sidebar-link-active' : '' }}">
                         My Data Backup
                    </a>
                </li>
                @else
                <li>
                    <div class="px-3 pb-1 pt-1">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">MAIN</p>
                    </div>
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'sidebar-link-active' : 'text-white' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12.75V21h6.75v-6.75h4.5V21H21v-8.25M4.5 9.75 12 3l7.5 6.75"/></svg>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li x-data="{ open: {{ request()->routeIs('admin.gpoa.*') || request()->routeIs('admin.activities') || request()->routeIs('activities.calendar') || request()->routeIs('admin.document-deadlines.*') ? 'true' : 'false' }} }">
                    <div class="px-3 pb-1 pt-2">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">DOCUMENTS &amp; ACTIVITIES</p>
                    </div>
                    <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-medium transition text-white">
                        <span class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5h7.5L19.5 8.25v9.75A2.25 2.25 0 0 1 17.25 20.25h-10.5A2.25 2.25 0 0 1 4.5 18V6.75A2.25 2.25 0 0 1 6.75 4.5h1.5Zm7.5 0v3.75h3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12h7.5M8.25 15.75h5.25"/></svg>
                            Documents &amp; Activities
                        </span>
                        <svg class="h-4 w-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" :class="open ? 'rotate-90' : ''" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <div x-show="open" x-transition class="mt-1 space-y-0.5 overflow-hidden pl-9">
                        <a href="{{ route('admin.gpoa.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.gpoa.*') ? 'sidebar-link-active' : 'text-white' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 19.5h13.5M7.5 16.5V8.25h9v8.25M9 11.25h6"/></svg>
                            <span>GPOA Monitoring</span>
                        </a>
                        <a href="{{ route('admin.activities') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.activities') ? 'sidebar-link-active' : 'text-white' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 18.75V7.5A1.5 1.5 0 0 1 6 6h12a1.5 1.5 0 0 1 1.5 1.5v11.25M7.5 10.5h9M7.5 14.25h6"/></svg>
                            <span>Activity Monitoring</span>
                        </a>
                        <a href="{{ route('activities.calendar') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('activities.calendar') ? 'sidebar-link-active' : 'text-white' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75v2.25M16.5 3.75v2.25M4.5 8.25h15M6 20.25h12A1.5 1.5 0 0 0 19.5 18.75V8.25H4.5v10.5A1.5 1.5 0 0 0 6 20.25Z"/></svg>
                            <span>Activity Calendar</span>
                        </a>
                        <a href="{{ route('admin.document-deadlines.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.document-deadlines.*') ? 'sidebar-link-active' : 'text-white' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 5.25h9L18.75 9v9.75A1.5 1.5 0 0 1 17.25 20.25h-9A1.5 1.5 0 0 1 6.75 18.75V6.75A1.5 1.5 0 0 1 8.25 5.25Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25v3.75h3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6M9 15.75h6"/></svg>
                            <span>Document Deadlines</span>
                        </a>
                    </div>
                </li>

                <li>
                    <div class="px-3 pb-1 pt-2">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">ORGANIZATION</p>
                    </div>
                    <a href="{{ route('admin.officers.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.officers.*') ? 'sidebar-link-active' : 'text-white' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75v-1.5A3 3 0 0 0 13.5 14.25H10.5a3 3 0 0 0-3 3v1.5M12 11.25a2.625 2.625 0 1 0 0-5.25 2.625 2.625 0 0 0 0 5.25Z"/></svg>
                        <span>Officer Directory</span>
                    </a>
                    <a href="{{ route('admin.officers.index', ['tab' => 'members']) }}" class="mt-0.5 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.officers.*') && request('tab') === 'members' ? 'sidebar-link-active' : 'text-white' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 18.75v-1.5A3.75 3.75 0 0 1 7.5 13.5h.75M15.75 13.5h.75A3.75 3.75 0 0 1 20.25 17.25v1.5M12 11.25a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/></svg>
                        <span>Org Chart</span>
                    </a>
                    <a href="{{ route('admin.summary-report') }}" class="mt-0.5 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.summary-report*') ? 'sidebar-link-active' : 'text-white' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 5.25h9L18.75 9v9.75A1.5 1.5 0 0 1 17.25 20.25h-9A1.5 1.5 0 0 1 6.75 18.75V6.75A1.5 1.5 0 0 1 8.25 5.25Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25v3.75h3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6M9 15.75h6"/></svg>
                        <span>Activity Overview Report</span>
                    </a>
                    <a href="{{ route('admin.organizations.index') }}" class="mt-0.5 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.organizations.*') ? 'sidebar-link-active' : 'text-white' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 14.25V6.75A1.5 1.5 0 0 1 6 5.25h12a1.5 1.5 0 0 1 1.5 1.5v7.5M4.5 14.25h15M18.75 19.5h-13.5v-5.25h13.5v5.25Z"/></svg>
                        <span>Organizations</span>
                    </a>
                </li>

                <li>
                    <div class="px-3 pb-1 pt-2">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">ACCOUNT &amp; SUPPORT</p>
                    </div>
                    <a href="{{ route('faq') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('faq') ? 'sidebar-link-active' : 'text-white' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75A2.25 2.25 0 0 1 12 7.5a2.25 2.25 0 1 1 2.25 3.75c-.96.59-1.5 1.29-1.5 2.25M12 17.25h.01M20.25 12a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z"/></svg>
                        <span>FAQ</span>
                    </a>

                    <div x-data="{ open: {{ request()->routeIs('admin.backups.*') || request()->routeIs('admin.maintenance.*') || request()->routeIs('admin.activity-logs.*') ? 'true' : 'false' }} }" class="mt-1">
                        <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-medium transition text-white">
                            <span class="flex items-center gap-3">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m19.4 15 .1.1a1.8 1.8 0 0 1-2.55 2.55l-.1-.1a1.8 1.8 0 0 0-3.07 1.27v.18a1.8 1.8 0 0 1-3.6 0v-.15a1.8 1.8 0 0 0-3.07-1.27l-.1.1A1.8 1.8 0 0 1 4.46 15.1l.1-.1a1.8 1.8 0 0 0-1.27-3.07H3.1a1.8 1.8 0 0 1 0-3.6h.15a1.8 1.8 0 0 0 1.27-3.07l-.1-.1a1.8 1.8 0 0 1 2.55-2.55l.1.1a1.8 1.8 0 0 0 3.07-1.27V1.4a1.8 1.8 0 0 1 3.6 0v.15a1.8 1.8 0 0 0 3.07 1.27l.1-.1a1.8 1.8 0 0 1 2.55 2.55l-.1.1a1.8 1.8 0 0 0 1.27 3.07h.18a1.8 1.8 0 0 1 0 3.6h-.15A1.8 1.8 0 0 0 19.4 15Z"/></svg>
                                <span>Settings</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" :class="open ? 'rotate-90' : ''" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>

                        <div x-show="open" x-transition class="mt-1 space-y-0.5 overflow-hidden pl-9">
                            <a href="{{ route('admin.backups.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.backups.*') ? 'sidebar-link-active' : 'text-white' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25V7.5A4.5 4.5 0 0 1 12 3a4.5 4.5 0 0 1 4.5 4.5v.75M5.25 10.5h13.5v9h-13.5z"/></svg>
                                <span>Backup &amp; Restore</span>
                            </a>
                            <a href="{{ route('admin.maintenance.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.maintenance.*') ? 'sidebar-link-active' : 'text-white' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v4.5M12 15.75v4.5M3.75 12h4.5M15.75 12h4.5M6.75 6.75l3.18 3.18M14.07 14.07l3.18 3.18M17.25 6.75 14.07 9.93M9.93 14.07 6.75 17.25"/></svg>
                                <span>System Maintenance</span>
                            </a>
                            <a href="{{ route('admin.activity-logs.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.activity-logs.*') ? 'sidebar-link-active' : 'text-white' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12.75h9M7.5 16.5h6M8.25 4.5h7.5L19.5 8.25v9.75A2.25 2.25 0 0 1 17.25 20.25h-10.5A2.25 2.25 0 0 1 4.5 18V6.75A2.25 2.25 0 0 1 6.75 4.5h1.5Zm7.5 0v3.75h3.75"/></svg>
                                <span>Activity Logs</span>
                            </a>
                        </div>
                    </div>
                </li>
                @endif

                <!-- Logout -->
                <li class="pt-4 mt-4 border-t border-white/10">
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="block px-4 py-2.5 rounded-lg transition font-bold text-white w-full text-left"
                       style="background-color: transparent;">
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
                    @php
                        $sidebarAvatarUrl = auth()->user()->avatar_url;
                    @endphp
                    <img src="{{ $sidebarAvatarUrl }}" data-fallback-src="{{ asset('images/osdw.logo.jpg') }}" alt="Profile photo" class="w-9 h-9 rounded-full object-cover border-2 border-white/10" onerror="this.onerror=null; this.src=this.dataset.fallbackSrc;"/>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-white/65 uppercase">{{ auth()->user()->role }}</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-white ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
                @if(!auth()->user()->isAdmin())
                <button type="button" @click="notificationsOpen = true; fetchUnreadCount()" class="relative ml-2 text-white focus:outline-none" aria-label="Open notifications">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"></span>
                </button>
                @endif
            </div>
        </div>
    </aside>

    @php
        $modalNotifications = auth()->user()?->notifications()->latest()->take(10)->get();
    @endphp

    <div x-show="notificationsOpen" x-cloak x-on:keydown.escape.window="notificationsOpen = false" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" role="dialog" aria-modal="true" aria-label="Notifications">
        <div class="fixed inset-0 bg-gray-500 opacity-75" x-on:click="notificationsOpen = false"></div>
        <div class="relative mb-6 bg-white rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-lg sm:mx-auto">
        <div class="bg-white rounded-t-lg px-6 py-5 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Notifications</h2>
                <p class="text-sm text-gray-500">Recent updates on your workflow and submissions.</p>
            </div>
            <button type="button" @click="notificationsOpen = false" class="text-gray-400 hover:text-gray-600 focus:outline-none" aria-label="Close notifications">
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
        </div>
    </div>

    <!-- Mobile overlay -->
    <div id="overlay" aria-hidden="true" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Main Content -->
    <div class="min-w-0 max-w-full flex-1 ml-0 md:ml-64 transition-all duration-300 overflow-auto">
        <!-- Top bar (mobile) -->
        <header class="w-full min-w-0 max-w-full bg-white shadow-sm h-14 flex items-center justify-between px-4 md:px-8 sticky top-0 z-10 md:hidden">
            <button id="sidebarToggle" type="button" aria-controls="sidebar" aria-expanded="false" aria-label="Open navigation menu" class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-md p-2 focus:outline-none">
                <svg class="h-6 w-6 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="font-bold text-gray-800">OrgTrack</span>
            @php
                $mobileAvatarUrl = auth()->user()->avatar_url;
            @endphp
            <img src="{{ $mobileAvatarUrl }}" data-fallback-src="{{ asset('images/osdw.logo.jpg') }}" alt="Profile photo" class="w-9 h-9 rounded-full object-cover" onerror="this.onerror=null; this.src=this.dataset.fallbackSrc;"/>
        </header>

        <!-- Page Content -->
        <main class="min-w-0 max-w-full p-3 md:p-8 w-full mx-auto md:max-w-7xl">
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const toggle  = document.getElementById('sidebarToggle');
    const closeButton = document.querySelector('[data-sidebar-close]');
    const open  = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        overlay.setAttribute('aria-hidden', 'false');
        toggle?.setAttribute('aria-expanded', 'true');
        toggle?.setAttribute('aria-label', 'Close navigation menu');
    };
    const close = () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        overlay.setAttribute('aria-hidden', 'true');
        toggle?.setAttribute('aria-expanded', 'false');
        toggle?.setAttribute('aria-label', 'Open navigation menu');
    };
    toggle  && toggle.addEventListener('click', () => sidebar.classList.contains('-translate-x-full') ? open() : close());
    closeButton?.addEventListener('click', close);
    overlay && overlay.addEventListener('click', close);
    document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
});
</script>
@include('components.confirm-modal')
@stack('scripts')
</body>
</html>
