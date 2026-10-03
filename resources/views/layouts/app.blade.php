<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OrgTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #sidebar { background: radial-gradient(circle at 100% 0%, #E65100 0%, rgba(230,81,0,0) 60%), linear-gradient(180deg, #3A1A06 0%, #2A1204 100%) !important; color: #FFFFFF; }
        #sidebar > div:first-child, #sidebar > div:last-child, #sidebar [class*="border-"] { border-color: rgba(255, 255, 255, 0.12) !important; }
        #sidebar > div:first-child h1 { color: #FFFFFF !important; }
        #sidebar > div:first-child p { color: rgba(255, 255, 255, 0.75) !important; }
        #sidebar nav p { color: rgba(255, 255, 255, 0.60) !important; }
        #sidebar nav { background-color: transparent !important; color: #FFFFFF; text-shadow: none !important; scrollbar-color: rgba(255, 255, 255, 0.25) transparent; }
        #sidebar nav::-webkit-scrollbar { width: 6px; }
        #sidebar nav::-webkit-scrollbar-track { background: transparent; }
        #sidebar nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.25); border-radius: 9999px; }
        #sidebar nav a, #sidebar nav button { color: #FFFFFF !important; background-color: transparent !important; text-shadow: none !important; }
        #sidebar nav a:hover, #sidebar nav button:hover { color: #FFFFFF !important; background-color: rgba(255, 255, 255, 0.08) !important; }
        #sidebar nav svg { color: rgba(255, 255, 255, 0.90); }
        #sidebar nav button[aria-expanded="true"] { color: #FFFFFF !important; background-color: rgba(255, 255, 255, 0.06) !important; }
        #sidebar nav button[aria-expanded="true"] svg:last-child { color: #FFB74D; }
        #sidebar nav a[style*="border-left-color"], #sidebar nav a[style*="linear-gradient"], #sidebar nav a[style*="#FFB74D"] { color: #FFFFFF !important; background: linear-gradient(90deg, rgba(255,183,77,0.40), rgba(255,183,77,0.12)) !important; box-shadow: inset 3px 0 #FFB74D; }
        #sidebar nav a[style*="border-left-color"] svg, #sidebar nav a[style*="linear-gradient"] svg, #sidebar nav a[style*="#FFB74D"] svg { color: #FFFFFF; }
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
@php
    $sidebarUser = auth()->user();
    $sidebarAccent = '#FFB74D';
@endphp

<div class="flex min-h-screen" x-data="{ notificationsOpen: false, profileMenuOpen: false }">

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-20 flex w-64 -translate-x-full flex-col text-white transition-transform duration-300 ease-in-out md:translate-x-0" style="background: radial-gradient(circle at 100% 0%, #E65100 0%, rgba(230,81,0,0) 60%), linear-gradient(180deg, #3A1A06 0%, #2A1204 100%);">
        <div class="border-b border-white/10 px-3 py-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/orgTracklogo.png') }}" alt="Orgtrack logo" class="h-10 w-10 rounded-md object-contain">
                <div>
                    <h1 class="text-lg font-semibold text-white">Orgtrack</h1>
                    <p class="mt-0.5 text-[11px] uppercase tracking-wide text-white/75">Activity Tracking System</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-0.5">
                @if(!auth()->user()->isAdmin())
                    <li>
                        <div class="px-3 pb-1 pt-1">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">MAIN</p>
                        </div>
                        <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('dashboard') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12.75V21h6.75v-6.75h4.5V21H21v-8.25M4.5 9.75 12 3l7.5 6.75"/></svg>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li x-data="{ open: {{ request()->routeIs('gpoa.*') || request()->routeIs('activity-monitor.*') || request()->routeIs('activity-requests.*') || request()->routeIs('activities.calendar') ? 'true' : 'false' }} }">
                        <div class="px-3 pb-1 pt-2">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">DOCUMENTS &amp; ACTIVITIES</p>
                        </div>
                        <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-medium text-white transition hover:bg-white/[0.08] hover:text-white" :class="open ? 'bg-white/[0.06] text-white' : ''">
                            <span class="flex items-center gap-3">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5h7.5L19.5 8.25v9.75A2.25 2.25 0 0 1 17.25 20.25h-10.5A2.25 2.25 0 0 1 4.5 18V6.75A2.25 2.25 0 0 1 6.75 4.5h1.5Zm7.5 0v3.75h3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12h7.5M8.25 15.75h5.25"/></svg>
                                Documents &amp; Activities
                            </span>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" :class="open ? 'rotate-90' : ''" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>

                        <div x-show="open" x-transition class="mt-1 space-y-0.5 overflow-hidden pl-9">
                            <a href="{{ route('gpoa.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('gpoa.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('gpoa.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 19.5h13.5M7.5 16.5V8.25h9v8.25M9 11.25h6"/></svg>
                                <span>My GPOA</span>
                            </a>
                            <a href="{{ route('activity-monitor.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('activity-monitor.*') || request()->routeIs('activity-requests.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('activity-monitor.*') || request()->routeIs('activity-requests.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 18.75V7.5A1.5 1.5 0 0 1 6 6h12a1.5 1.5 0 0 1 1.5 1.5v11.25M7.5 10.5h9M7.5 14.25h6"/></svg>
                                <span>Activity Monitor</span>
                            </a>
                            <a href="{{ route('activities.calendar') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('activities.calendar') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('activities.calendar') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75v2.25M16.5 3.75v2.25M4.5 8.25h15M6 20.25h12A1.5 1.5 0 0 0 19.5 18.75V8.25H4.5v10.5A1.5 1.5 0 0 0 6 20.25Z"/></svg>
                                <span>Activity Calendar</span>
                            </a>
                        </div>
                    </li>

                    <li>
                        <div class="px-3 pb-1 pt-2">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">ORGANIZATION</p>
                        </div>
                        <a href="{{ route('organization.officers.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('organization.officers.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('organization.officers.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75v-1.5A3 3 0 0 0 13.5 14.25H10.5a3 3 0 0 0-3 3v1.5M12 11.25a2.625 2.625 0 1 0 0-5.25 2.625 2.625 0 0 0 0 5.25Z"/></svg>
                            <span>Officer Archive</span>
                        </a>
                    </li>

                    <li>
                        <div class="px-3 pb-1 pt-2">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">ACCOUNT &amp; SUPPORT</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('profile.edit') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('profile.edit') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Zm-9 12.75A6.75 6.75 0 0 1 13.5 12.75 6.75 6.75 0 0 1 20.25 19.5"/></svg>
                            <span>Edit Profile</span>
                        </a>
                        <a href="{{ route('my-backup.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('my-backup.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('my-backup.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25V7.5A4.5 4.5 0 0 1 12 3a4.5 4.5 0 0 1 4.5 4.5v.75M5.25 10.5h13.5v9h-13.5z"/></svg>
                            <span>My Data Backup</span>
                        </a>
                        <a href="{{ route('faq') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('faq') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('faq') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75A2.25 2.25 0 0 1 12 7.5a2.25 2.25 0 1 1 2.25 3.75c-.96.59-1.5 1.29-1.5 2.25M12 17.25h.01M20.25 12a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z"/></svg>
                            <span>FAQ</span>
                        </a>
                    </li>
                @else
                    <li>
                        <div class="px-3 pb-1 pt-1">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">MAIN</p>
                        </div>
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('admin.dashboard') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12.75V21h6.75v-6.75h4.5V21H21v-8.25M4.5 9.75 12 3l7.5 6.75"/></svg>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li x-data="{ open: {{ request()->routeIs('admin.gpoa.*') || request()->routeIs('admin.activities') || request()->routeIs('activities.calendar') || request()->routeIs('admin.document-deadlines.*') ? 'true' : 'false' }} }">
                        <div class="px-3 pb-1 pt-2">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">DOCUMENTS &amp; ACTIVITIES</p>
                        </div>
                        <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-medium text-white transition hover:bg-white/[0.08] hover:text-white" :class="open ? 'bg-white/[0.06] text-white' : ''">
                            <span class="flex items-center gap-3">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5h7.5L19.5 8.25v9.75A2.25 2.25 0 0 1 17.25 20.25h-10.5A2.25 2.25 0 0 1 4.5 18V6.75A2.25 2.25 0 0 1 6.75 4.5h1.5Zm7.5 0v3.75h3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12h7.5M8.25 15.75h5.25"/></svg>
                                Documents &amp; Activities
                            </span>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" :class="open ? 'rotate-90' : ''" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>

                        <div x-show="open" x-transition class="mt-1 space-y-0.5 overflow-hidden pl-9">
                            <a href="{{ route('admin.gpoa.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.gpoa.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('admin.gpoa.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 19.5h13.5M7.5 16.5V8.25h9v8.25M9 11.25h6"/></svg>
                                <span>GPOA</span>
                            </a>
                            <a href="{{ route('admin.activities') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.activities') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('admin.activities') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 18.75V7.5A1.5 1.5 0 0 1 6 6h12a1.5 1.5 0 0 1 1.5 1.5v11.25M7.5 10.5h9M7.5 14.25h6"/></svg>
                                <span>Activity Monitoring</span>
                            </a>
                            <a href="{{ route('activities.calendar') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('activities.calendar') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('activities.calendar') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75v2.25M16.5 3.75v2.25M4.5 8.25h15M6 20.25h12A1.5 1.5 0 0 0 19.5 18.75V8.25H4.5v10.5A1.5 1.5 0 0 0 6 20.25Z"/></svg>
                                <span>Calendar</span>
                            </a>
                            <a href="{{ route('admin.document-deadlines.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.document-deadlines.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('admin.document-deadlines.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 5.25h9L18.75 9v9.75A1.5 1.5 0 0 1 17.25 20.25h-9A1.5 1.5 0 0 1 6.75 18.75V6.75A1.5 1.5 0 0 1 8.25 5.25Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25v3.75h3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6M9 15.75h6"/></svg>
                                <span>Document Deadlines</span>
                            </a>
                        </div>
                    </li>

                    <li>
                        <div class="px-3 pb-1 pt-2">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">ORGANIZATION</p>
                        </div>
                        <a href="{{ route('admin.officers.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.officers.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('admin.officers.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75v-1.5A3 3 0 0 0 13.5 14.25H10.5a3 3 0 0 0-3 3v1.5M12 11.25a2.625 2.625 0 1 0 0-5.25 2.625 2.625 0 0 0 0 5.25Z"/></svg>
                            <span>Officers</span>
                        </a>
                        <a href="{{ route('admin.summary-report') }}" class="mt-0.5 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.summary-report*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('admin.summary-report*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 5.25h9L18.75 9v9.75A1.5 1.5 0 0 1 17.25 20.25h-9A1.5 1.5 0 0 1 6.75 18.75V6.75A1.5 1.5 0 0 1 8.25 5.25Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25v3.75h3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6M9 15.75h6"/></svg>
                            <span>Summary report</span>
                        </a>
                        <a href="{{ route('admin.organizations.index') }}" class="mt-0.5 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.organizations.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('admin.organizations.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 14.25V6.75A1.5 1.5 0 0 1 6 5.25h12a1.5 1.5 0 0 1 1.5 1.5v7.5M4.5 14.25h15M18.75 19.5h-13.5v-5.25h13.5v5.25Z"/></svg>
                            <span>Organizations</span>
                        </a>
                    </li>

                    <li>
                        <div class="px-3 pb-1 pt-2">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">ACCOUNT &amp; SUPPORT</p>
                        </div>
                        <a href="{{ route('faq') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('faq') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('faq') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75A2.25 2.25 0 0 1 12 7.5a2.25 2.25 0 1 1 2.25 3.75c-.96.59-1.5 1.29-1.5 2.25M12 17.25h.01M20.25 12a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z"/></svg>
                            <span>FAQ</span>
                        </a>

                        <div x-data="{ open: {{ request()->routeIs('admin.backups.*') || request()->routeIs('admin.maintenance.*') || request()->routeIs('admin.activity-logs.*') ? 'true' : 'false' }} }" class="mt-1">
                            <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-medium transition hover:bg-white/[0.08] hover:text-white" :class="open ? 'bg-white/[0.06] text-white' : 'text-white'">
                                <span class="flex items-center gap-3">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m19.4 15 .1.1a1.8 1.8 0 0 1-2.55 2.55l-.1-.1a1.8 1.8 0 0 0-3.07 1.27v.18a1.8 1.8 0 0 1-3.6 0v-.15a1.8 1.8 0 0 0-3.07-1.27l-.1.1A1.8 1.8 0 0 1 4.46 15.1l.1-.1a1.8 1.8 0 0 0-1.27-3.07H3.1a1.8 1.8 0 0 1 0-3.6h.15a1.8 1.8 0 0 0 1.27-3.07l-.1-.1a1.8 1.8 0 0 1 2.55-2.55l.1.1a1.8 1.8 0 0 0 3.07-1.27V1.4a1.8 1.8 0 0 1 3.6 0v.15a1.8 1.8 0 0 0 3.07 1.27l.1-.1a1.8 1.8 0 0 1 2.55 2.55l-.1.1a1.8 1.8 0 0 0 1.27 3.07h.18a1.8 1.8 0 0 1 0 3.6h-.15A1.8 1.8 0 0 0 19.4 15Z"/></svg>
                                    <span>Settings</span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" :class="open ? 'rotate-90' : ''" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>

                            <div x-show="open" x-transition class="mt-1 space-y-0.5 overflow-hidden pl-9">
                                <a href="{{ route('admin.backups.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.backups.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('admin.backups.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25V7.5A4.5 4.5 0 0 1 12 3a4.5 4.5 0 0 1 4.5 4.5v.75M5.25 10.5h13.5v9h-13.5z"/></svg>
                                    <span>Backup &amp; Restore</span>
                                </a>
                                <a href="{{ route('admin.maintenance.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.maintenance.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('admin.maintenance.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v4.5M12 15.75v4.5M3.75 12h4.5M15.75 12h4.5M6.75 6.75l3.18 3.18M14.07 14.07l3.18 3.18M17.25 6.75 14.07 9.93M9.93 14.07 6.75 17.25"/></svg>
                                    <span>System Maintenance</span>
                                </a>
                                <a href="{{ route('admin.activity-logs.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.activity-logs.*') ? 'border-l-[3px] bg-[#FFB74D] text-white' : 'text-white hover:bg-white/[0.08] hover:text-white' }}" style="{{ request()->routeIs('admin.activity-logs.*') ? 'border-left-color: ' . $sidebarAccent . ';' : '' }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12.75h9M7.5 16.5h6M8.25 4.5h7.5L19.5 8.25v9.75A2.25 2.25 0 0 1 17.25 20.25h-10.5A2.25 2.25 0 0 1 4.5 18V6.75A2.25 2.25 0 0 1 6.75 4.5h1.5Zm7.5 0v3.75h3.75"/></svg>
                                    <span>Activity Logs</span>
                                </a>
                            </div>
                        </div>
                    </li>
                @endif
            </ul>
        </nav>

        <div class="mt-auto border-t border-white/10 p-3" x-data="{ profileMenuOpen: false }">
            <div class="flex items-center justify-between gap-2">
                <button type="button" @click="profileMenuOpen = !profileMenuOpen" class="flex flex-1 items-center gap-3 rounded-md px-2 py-2 text-left transition hover:bg-white/[0.08]">
                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }} profile photo" class="h-9 w-9 rounded-full object-cover ring-1 ring-white/10" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-[11px] text-white/75 uppercase">{{ auth()->user()->role }}</p>
                    </div>
                </button>

                <button type="button" @click="notificationsOpen = true" class="relative inline-flex h-8 w-8 items-center justify-center rounded-md text-white transition hover:bg-white/[0.08] hover:text-white" aria-label="Open notifications">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0h-6"/></svg>
                    @if(auth()->user()->unreadNotificationsCount() > 0)
                        <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white">{{ auth()->user()->unreadNotificationsCount() }}</span>
                    @endif
                </button>
            </div>

            <div x-show="profileMenuOpen" x-transition class="mt-2 rounded-md border border-white/10 bg-black/15 p-2">
                <a href="{{ route('profile.edit') }}" class="block rounded-md px-2 py-1.5 text-sm text-white transition hover:bg-white/[0.08] hover:text-white">Edit Profile</a>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();" class="mt-1 block rounded-md px-2 py-1.5 text-sm text-white transition hover:bg-white/[0.08] hover:text-[#FFB74D]">Logout</a>
                <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
            </div>
        </div>
    </aside>

    @php
        $modalNotifications = auth()->user()->notifications()->latest()->take(10)->get();
    @endphp

    <div x-show="notificationsOpen" x-on:keydown.escape.window="notificationsOpen = false" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" role="dialog" aria-modal="true" aria-label="Notifications">
        <div class="fixed inset-0 bg-gray-500 opacity-75" x-on:click="notificationsOpen = false"></div>
        <div class="relative mb-6 bg-white rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-lg sm:mx-auto">
        <div class="bg-white rounded-t-lg px-6 py-5 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Notifications</h2>
                <p class="text-sm text-gray-500">Recent updates on your activities and submissions.</p>
            </div>
            <button type="button" @click="notificationsOpen = false" class="text-gray-400 hover:text-gray-600 focus:outline-none" aria-label="Close notifications">
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

            @forelse($modalNotifications as $notification)
                <div class="rounded-xl border p-4 mb-3 flex items-start justify-between gap-4 {{ $notification->read_at ? 'border-gray-200 bg-white' : 'border-blue-300 bg-blue-50' }}">
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
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-gray-500">
                    No notifications yet.
                </div>
            @endforelse
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
            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }} profile photo" class="w-9 h-9 rounded-full object-cover"/>
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
@include('partials.file-viewer-modal')
@include('components.confirm-modal')
@stack('scripts')
</body>
</html>
