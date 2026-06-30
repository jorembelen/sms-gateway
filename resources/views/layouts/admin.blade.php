<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>{{ $pageTitle ?? 'Admin' }} – SMS Gateway</title>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="assets/favicon/site.webmanifest">

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "error": "#ba1a1a",
                        "secondary-container": "#dae2fd",
                        "surface-tint": "#4d44e3",
                        "surface-variant": "#d3e4fe",
                        "secondary": "#565e74",
                        "inverse-on-surface": "#eaf1ff",
                        "on-tertiary-container": "#ffd2be",
                        "on-error-container": "#93000a",
                        "secondary-fixed": "#dae2fd",
                        "surface-dim": "#cbdbf5",
                        "on-surface-variant": "#464555",
                        "surface-container-low": "#eff4ff",
                        "on-tertiary": "#ffffff",
                        "surface-container-lowest": "#ffffff",
                        "surface-container": "#e5eeff",
                        "inverse-surface": "#213145",
                        "on-secondary-container": "#5c647a",
                        "tertiary-container": "#a44100",
                        "background": "#f8f9ff",
                        "surface-container-high": "#dce9ff",
                        "outline-variant": "#c7c4d8",
                        "surface-container-highest": "#d3e4fe",
                        "error-container": "#ffdad6",
                        "on-background": "#0b1c30",
                        "primary-container": "#4f46e5",
                        "on-primary": "#ffffff",
                        "on-secondary": "#ffffff",
                        "surface": "#f8f9ff",
                        "primary": "#3525cd",
                        "primary-fixed-dim": "#c3c0ff",
                        "on-secondary-fixed": "#131b2e",
                        "on-surface": "#0b1c30",
                        "on-primary-container": "#dad7ff",
                        "surface-bright": "#f8f9ff",
                        "primary-fixed": "#e2dfff",
                        "outline": "#777587",
                        "tertiary": "#7e3000",
                        "on-error": "#ffffff",
                        "inverse-primary": "#c3c0ff",
                        "tertiary-fixed-dim": "#ffb695",
                    },
                    borderRadius: {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem",
                    },
                    spacing: {
                        "gutter": "24px",
                        "stack_gap_sm": "8px",
                        "stack_gap_md": "16px",
                        "stack_gap_lg": "24px",
                        "sidebar_width": "280px",
                        "sidebar_collapsed": "80px",
                        "container_max_width": "1440px",
                    },
                    fontFamily: {
                        "headline-md": ["Inter"],
                        "label-sm": ["JetBrains Mono"],
                        "display-sm": ["Inter"],
                        "body-sm": ["Inter"],
                        "headline-lg": ["Inter"],
                        "body-lg": ["Inter"],
                        "body-md": ["Inter"],
                        "label-md": ["JetBrains Mono"],
                    },
                    fontSize: {
                        "headline-md": ["20px", {
                            "lineHeight": "28px",
                            "fontWeight": "600"
                        }],
                        "label-sm": ["11px", {
                            "lineHeight": "14px",
                            "fontWeight": "500"
                        }],
                        "display-sm": ["30px", {
                            "lineHeight": "38px",
                            "letterSpacing": "-0.02em",
                            "fontWeight": "700"
                        }],
                        "body-sm": ["12px", {
                            "lineHeight": "18px",
                            "fontWeight": "400"
                        }],
                        "headline-lg": ["24px", {
                            "lineHeight": "32px",
                            "letterSpacing": "-0.01em",
                            "fontWeight": "600"
                        }],
                        "body-lg": ["16px", {
                            "lineHeight": "24px",
                            "fontWeight": "400"
                        }],
                        "body-md": ["14px", {
                            "lineHeight": "20px",
                            "fontWeight": "400"
                        }],
                        "label-md": ["13px", {
                            "lineHeight": "16px",
                            "fontWeight": "500"
                        }],
                    },
                },
            },
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #D1D5DB;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #9CA3AF;
        }
    </style>

    @livewireStyles
    @stack('styles')
</head>

<body class="bg-background text-on-surface" x-data="{ sidebarOpen: false }">

    {{-- Mobile sidebar overlay --}}
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="sidebarOpen = false" class="fixed inset-0 bg-black/30 z-30 lg:hidden"
        style="display:none"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed left-0 top-0 h-full z-40 flex flex-col bg-surface border-r border-outline-variant w-[280px] transition-transform duration-200 ease-in-out">
        <div class="p-gutter flex items-center gap-3">
            <img src="/assets/logo.jpeg" alt="SMS Gateway" class="w-10 h-10 rounded object-cover flex-shrink-0" />
            <div>
                <h1 class="font-headline-lg text-headline-lg font-bold text-on-surface leading-tight">SMS Gateway</h1>
                <p class="font-label-md text-label-md text-on-surface-variant">Admin Console</p>
            </div>
        </div>

        <nav class="flex-1 px-4 mt-2 space-y-1 overflow-y-auto custom-scrollbar">
            <a href="{{ route('admin.dashboard') }}" @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 ease-in-out',
                'text-primary bg-secondary-container font-bold' => request()->routeIs(
                    'admin.dashboard'),
                'text-on-surface-variant hover:bg-surface-container-high hover:text-primary' => !request()->routeIs(
                    'admin.dashboard'),
            ])>
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-label-md text-label-md">Dashboard</span>
            </a>

            <a href="{{ route('admin.messages') }}" @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 ease-in-out',
                'text-primary bg-secondary-container font-bold' => request()->routeIs(
                    'admin.messages'),
                'text-on-surface-variant hover:bg-surface-container-high hover:text-primary' => !request()->routeIs(
                    'admin.messages'),
            ])>
                <span class="material-symbols-outlined">sms</span>
                <span class="font-label-md text-label-md">Messages</span>
            </a>

            <a href="{{ route('admin.devices') }}" @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 ease-in-out',
                'text-primary bg-secondary-container font-bold' => request()->routeIs(
                    'admin.devices'),
                'text-on-surface-variant hover:bg-surface-container-high hover:text-primary' => !request()->routeIs(
                    'admin.devices'),
            ])>
                <span class="material-symbols-outlined">settings_remote</span>
                <span class="font-label-md text-label-md">Devices</span>
            </a>

            <a href="{{ route('admin.failed') }}" @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 ease-in-out',
                'text-primary bg-secondary-container font-bold' => request()->routeIs(
                    'admin.failed'),
                'text-on-surface-variant hover:bg-surface-container-high hover:text-primary' => !request()->routeIs(
                    'admin.failed'),
            ])>
                <span class="material-symbols-outlined">report_problem</span>
                <span class="font-label-md text-label-md">Failed / Alerts</span>
            </a>

            <a href="{{ route('admin.blast') }}" @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 ease-in-out',
                'text-primary bg-secondary-container font-bold' => request()->routeIs(
                    'admin.blast'),
                'text-on-surface-variant hover:bg-surface-container-high hover:text-primary' => !request()->routeIs(
                    'admin.blast'),
            ])>
                <span class="material-symbols-outlined">campaign</span>
                <span class="font-label-md text-label-md">Blast SMS</span>
            </a>

            <a href="{{ route('admin.queue') }}" @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 ease-in-out',
                'text-primary bg-secondary-container font-bold' => request()->routeIs(
                    'admin.queue'),
                'text-on-surface-variant hover:bg-surface-container-high hover:text-primary' => !request()->routeIs(
                    'admin.queue'),
            ])>
                <span class="material-symbols-outlined">manufacturing</span>
                <span class="font-label-md text-label-md">Queue Monitor</span>
            </a>
        </nav>

        <div class="px-4 py-6 border-t border-outline-variant space-y-1">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2 text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors duration-200 ease-in-out rounded-lg">
                    <span class="material-symbols-outlined">logout</span>
                    <span class="font-label-md text-label-md">Log out</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="lg:ml-[280px] min-h-screen flex flex-col">

        {{-- Top bar --}}
        <header
            class="sticky top-0 right-0 z-30 flex justify-between items-center w-full px-6 h-16 bg-surface border-b border-outline-variant">
            <div class="flex items-center gap-4">
                {{-- Mobile hamburger --}}
                <button @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden p-2 rounded-full hover:bg-surface-container-high text-on-surface-variant">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <h2 class="font-headline-md text-headline-md font-semibold text-on-surface">
                    {{ $pageTitle ?? '' }}
                </h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-white text-[18px]">person</span>
                </div>
                <span
                    class="font-label-md text-label-md text-on-surface hidden lg:block">{{ auth()->user()->name }}</span>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-gutter max-w-[1440px] w-full mx-auto">
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
    @livewireScripts
</body>

</html>
