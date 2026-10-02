<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orgtrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
<div class="flex min-h-screen">

    <!-- Sidebar -->
    <div
        x-data="{
            notificationsOpen: false,
            unreadCount: @js(auth()->user()->unreadNotificationsCount()),
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
    <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 text-white transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-20 flex flex-col" style="background-color: #b45309;">
        <div class="p-5 border-b border-white/10">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/orgTracklogo.png') }}" alt="Orgtrack logo" class="h-10 w-10 object-contain rounded-md">
                <div>
                    <h1 class="text-xl font-bold text-white">Orgtrack</h1>
                    <p class="text-xs text-white/80 mt-0.5">Activity Tracking System</p>
                </div>
            </div>
        </div>
        <nav class="p-4 flex-1 overflow-y-auto bg-black/10" style="text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
            <ul class="space-y-1">

                @if(!auth()->user()->isAdmin())
                <!-- USER MENU -->
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('dashboard') ? '#e89600' : 'rgba(0,0,0,0.12)' }}; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                         Dashboard
                    </a>
                </li>
                <li x-data="{ open: {{ request()->routeIs('gpoa.*') || request()->routeIs('activities.calendar') || request()->routeIs('activity-monitor.*') || request()->routeIs('activity-requests.*') ? 'true' : 'false' }} }">
                    <button type="button"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            class="flex items-center justify-between gap-3 w-full px-4 py-2.5 rounded-lg transition font-bold text-white"
                            :style="open || {{ request()->routeIs('gpoa.*') || request()->routeIs('activities.calendar') || request()->routeIs('activity-monitor.*') || request()->routeIs('activity-requests.*') ? 'true' : 'false' }} ? 'background-color: #e89600; text-shadow: 0 1px 3px rgba(0,0,0,0.5);' : 'background-color: rgba(0,0,0,0.12); text-shadow: 0 1px 3px rgba(0,0,0,0.5);'">
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
                           class="block px-4 py-2 rounded-lg transition font-bold text-white"
                           style="background-color: {{ request()->routeIs('gpoa.*') ? '#e89600' : 'rgba(0,0,0,0.12)' }}; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                            My GPOA
                        </a>
                                <a href="{{ route('activity-monitor.index') }}"
                           class="block px-4 py-2 rounded-lg transition font-bold text-white"
                                    style="background-color: {{ request()->routeIs('activity-monitor.*') || request()->routeIs('activity-requests.*') ? '#e89600' : 'rgba(0,0,0,0.12)' }}; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                                     Activity Monitor
                        </a>
                        <a href="{{ route('activities.calendar') }}"
                           class="block px-4 py-2 rounded-lg transition font-bold text-white"
                           style="background-color: {{ request()->routeIs('activities.calendar') ? '#e89600' : 'rgba(0,0,0,0.12)' }}; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                            Activity Calendar
                        </a>
                    </div>
                </li>
                <li>
                    <a href="{{ route('organization.officers.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('organization.officers.*') ? '#e89600' : 'rgba(0,0,0,0.12)' }}; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                         Officer Archive
                    </a>
                </li>
                <li>
                    <a href="{{ route('organization.members.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('organization.members.*') ? '#e89600' : 'rgba(0,0,0,0.12)' }}; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                         Org Chart
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('profile.edit') ? '#e89600' : 'rgba(0,0,0,0.12)' }}; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                         Edit Profile
                    </a>
                </li>
                <li>
                    <a href="{{ route('my-backup.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition font-bold text-white"
                       style="background-color: {{ request()->routeIs('my-backup.*') ? '#e89600' : 'rgba(0,0,0,0.12)' }}; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                         My Data Backup
                    </a>
                </li>
                @else
                <li>
                    <div class="px-3 pb-1 pt-1">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">MAIN</p>
                    </div>
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                       style="{{ request()->routeIs('admin.dashboard') ? 'border-left-color: #f59e0b;' : '' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12.75V21h6.75v-6.75h4.5V21H21v-8.25M4.5 9.75 12 3l7.5 6.75"/></svg>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li x-data="{ open: {{ request()->routeIs('admin.gpoa.*') || request()->routeIs('admin.activities') || request()->routeIs('activities.calendar') || request()->routeIs('admin.document-deadlines.*') ? 'true' : 'false' }} }">
                    <div class="px-3 pb-1 pt-2">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">DOCUMENTS &amp; ACTIVITIES</p>
                    </div>
                    <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-medium transition hover:bg-white/5 hover:text-white" :class="open ? 'bg-white/5 text-white' : 'text-slate-300'">
                        <span class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5h7.5L19.5 8.25v9.75A2.25 2.25 0 0 1 17.25 20.25h-10.5A2.25 2.25 0 0 1 4.5 18V6.75A2.25 2.25 0 0 1 6.75 4.5h1.5Zm7.5 0v3.75h3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12h7.5M8.25 15.75h5.25"/></svg>
                            Documents &amp; Activities
                        </span>
                        <svg class="h-4 w-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" :class="open ? 'rotate-90' : ''" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <div x-show="open" x-transition class="mt-1 space-y-0.5 overflow-hidden pl-9">
                        <a href="{{ route('admin.gpoa.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.gpoa.*') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('admin.gpoa.*') ? 'border-left-color: #f59e0b;' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 19.5h13.5M7.5 16.5V8.25h9v8.25M9 11.25h6"/></svg>
                            <span>GPOA Monitoring</span>
                        </a>
                        <a href="{{ route('admin.activities') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.activities') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('admin.activities') ? 'border-left-color: #f59e0b;' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 18.75V7.5A1.5 1.5 0 0 1 6 6h12a1.5 1.5 0 0 1 1.5 1.5v11.25M7.5 10.5h9M7.5 14.25h6"/></svg>
                            <span>Activity Monitoring</span>
                        </a>
                        <a href="{{ route('activities.calendar') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('activities.calendar') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('activities.calendar') ? 'border-left-color: #f59e0b;' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75v2.25M16.5 3.75v2.25M4.5 8.25h15M6 20.25h12A1.5 1.5 0 0 0 19.5 18.75V8.25H4.5v10.5A1.5 1.5 0 0 0 6 20.25Z"/></svg>
                            <span>Activity Calendar</span>
                        </a>
                        <a href="{{ route('admin.document-deadlines.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.document-deadlines.*') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('admin.document-deadlines.*') ? 'border-left-color: #f59e0b;' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 5.25h9L18.75 9v9.75A1.5 1.5 0 0 1 17.25 20.25h-9A1.5 1.5 0 0 1 6.75 18.75V6.75A1.5 1.5 0 0 1 8.25 5.25Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25v3.75h3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6M9 15.75h6"/></svg>
                            <span>Document Deadlines</span>
                        </a>
                    </div>
                </li>

                <li>
                    <div class="px-3 pb-1 pt-2">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">ORGANIZATION</p>
                    </div>
                    <a href="{{ route('admin.officers.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.officers.*') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('admin.officers.*') ? 'border-left-color: #f59e0b;' : '' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75v-1.5A3 3 0 0 0 13.5 14.25H10.5a3 3 0 0 0-3 3v1.5M12 11.25a2.625 2.625 0 1 0 0-5.25 2.625 2.625 0 0 0 0 5.25Z"/></svg>
                        <span>Officer Directory</span>
                    </a>
                    <a href="{{ route('admin.officers.index', ['tab' => 'members']) }}" class="mt-0.5 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.officers.*') && request('tab') === 'members' ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('admin.officers.*') && request('tab') === 'members' ? 'border-left-color: #f59e0b;' : '' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 18.75v-1.5A3.75 3.75 0 0 1 7.5 13.5h.75M15.75 13.5h.75A3.75 3.75 0 0 1 20.25 17.25v1.5M12 11.25a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/></svg>
                        <span>Org Chart</span>
                    </a>
                    <a href="{{ route('admin.summary-report') }}" class="mt-0.5 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.summary-report*') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('admin.summary-report*') ? 'border-left-color: #f59e0b;' : '' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 5.25h9L18.75 9v9.75A1.5 1.5 0 0 1 17.25 20.25h-9A1.5 1.5 0 0 1 6.75 18.75V6.75A1.5 1.5 0 0 1 8.25 5.25Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25v3.75h3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6M9 15.75h6"/></svg>
                        <span>Activity Overview Report</span>
                    </a>
                    <a href="{{ route('admin.organizations.index') }}" class="mt-0.5 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.organizations.*') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('admin.organizations.*') ? 'border-left-color: #f59e0b;' : '' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 14.25V6.75A1.5 1.5 0 0 1 6 5.25h12a1.5 1.5 0 0 1 1.5 1.5v7.5M4.5 14.25h15M18.75 19.5h-13.5v-5.25h13.5v5.25Z"/></svg>
                        <span>Organizations</span>
                    </a>
                </li>

                <li>
                    <div class="px-3 pb-1 pt-2">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">ACCOUNT &amp; SUPPORT</p>
                    </div>
                    <a href="{{ route('faq') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('faq') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('faq') ? 'border-left-color: #f59e0b;' : '' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75A2.25 2.25 0 0 1 12 7.5a2.25 2.25 0 1 1 2.25 3.75c-.96.59-1.5 1.29-1.5 2.25M12 17.25h.01M20.25 12a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z"/></svg>
                        <span>FAQ</span>
                    </a>

                    <div x-data="{ open: {{ request()->routeIs('admin.backups.*') || request()->routeIs('admin.maintenance.*') || request()->routeIs('admin.activity-logs.*') ? 'true' : 'false' }} }" class="mt-1">
                        <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-medium transition hover:bg-white/5 hover:text-white" :class="open ? 'bg-white/5 text-white' : 'text-slate-300'">
                            <span class="flex items-center gap-3">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m19.4 15 .1.1a1.8 1.8 0 0 1-2.55 2.55l-.1-.1a1.8 1.8 0 0 0-3.07 1.27v.18a1.8 1.8 0 0 1-3.6 0v-.15a1.8 1.8 0 0 0-3.07-1.27l-.1.1A1.8 1.8 0 0 1 4.46 15.1l.1-.1a1.8 1.8 0 0 0-1.27-3.07H3.1a1.8 1.8 0 0 1 0-3.6h.15a1.8 1.8 0 0 0 1.27-3.07l-.1-.1a1.8 1.8 0 0 1 2.55-2.55l.1.1a1.8 1.8 0 0 0 3.07-1.27V1.4a1.8 1.8 0 0 1 3.6 0v.15a1.8 1.8 0 0 0 3.07 1.27l.1-.1a1.8 1.8 0 0 1 2.55 2.55l-.1.1a1.8 1.8 0 0 0 1.27 3.07h.18a1.8 1.8 0 0 1 0 3.6h-.15A1.8 1.8 0 0 0 19.4 15Z"/></svg>
                                <span>Settings</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" :class="open ? 'rotate-90' : ''" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>

                        <div x-show="open" x-transition class="mt-1 space-y-0.5 overflow-hidden pl-9">
                            <a href="{{ route('admin.backups.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.backups.*') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('admin.backups.*') ? 'border-left-color: #f59e0b;' : '' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25V7.5A4.5 4.5 0 0 1 12 3a4.5 4.5 0 0 1 4.5 4.5v.75M5.25 10.5h13.5v9h-13.5z"/></svg>
                                <span>Backup &amp; Restore</span>
                            </a>
                            <a href="{{ route('admin.maintenance.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.maintenance.*') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('admin.maintenance.*') ? 'border-left-color: #f59e0b;' : '' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v4.5M12 15.75v4.5M3.75 12h4.5M15.75 12h4.5M6.75 6.75l3.18 3.18M14.07 14.07l3.18 3.18M17.25 6.75 14.07 9.93M9.93 14.07 6.75 17.25"/></svg>
                                <span>System Maintenance</span>
                            </a>
                            <a href="{{ route('admin.activity-logs.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.activity-logs.*') ? 'border-l-[3px] bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" style="{{ request()->routeIs('admin.activity-logs.*') ? 'border-left-color: #f59e0b;' : '' }}">
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
                       class="block px-4 py-2.5 rounded-lg transition font-bold text-white/80 hover:bg-white/10 w-full text-left"
                       style="background-color: rgba(0,0,0,0.12); text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
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
                    <img src="{{ $sidebarAvatarUrl }}" alt="Profile photo" class="w-9 h-9 rounded-full object-cover border-2 border-white/40" onerror="this.onerror=null; this.src='{{ asset('images/osdw.logo.jpg') }}';"/>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-white/70 uppercase">{{ auth()->user()->role }}</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-white/80 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
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
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Main Content -->
    <div class="min-w-0 max-w-full flex-1 ml-0 md:ml-64 transition-all duration-300 overflow-auto">
        <!-- Top bar (mobile) -->
        <header class="w-full min-w-0 max-w-full bg-white shadow-sm h-14 flex items-center justify-between px-4 md:px-8 sticky top-0 z-10 md:hidden">
            <button id="sidebarToggle" class="p-2 focus:outline-none">
                <svg class="h-6 w-6 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="font-bold text-gray-800">Orgtrack</span>
            @php
                $mobileAvatarUrl = auth()->user()->avatar_url;
            @endphp
            <img src="{{ $mobileAvatarUrl }}" alt="Profile photo" class="w-9 h-9 rounded-full object-cover" onerror="this.onerror=null; this.src='{{ asset('images/osdw.logo.jpg') }}';"/>
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
    const open  = () => { sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); };
    const close = () => { sidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); };
    toggle  && toggle.addEventListener('click', () => sidebar.classList.contains('-translate-x-full') ? open() : close());
    overlay && overlay.addEventListener('click', close);
});
</script>
@stack('scripts')
</body>
</html>
