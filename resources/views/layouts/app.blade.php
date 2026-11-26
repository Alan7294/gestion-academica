@php
    $usuario = auth()->user();

    $iniciales = collect(explode(' ', trim($usuario->name)))
        ->filter()
        ->take(2)
        ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
        ->implode('');

    $sinLeer = $usuario->unreadNotifications()->count();
    $rolActual = $usuario->rolPrincipal();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Gestión Académica') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            {{-- Fondo oscuro al abrir el menú en móvil --}}
            <div x-show="sidebarOpen"
                 x-transition.opacity
                 class="fixed inset-0 z-40 bg-ink-950/60 backdrop-blur-sm lg:hidden"
                 style="display: none;"
                 @click="sidebarOpen = false"></div>

            {{-- Menú lateral --}}
            <aside class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col bg-ink-950 transition-transform duration-300 ease-out lg:translate-x-0"
                   :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   aria-label="{{ __('Navegación principal') }}">
                <div class="flex items-center gap-3 border-b border-white/10 px-5 py-5">
                    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                        <x-application-logo class="h-10 w-10 shrink-0" />
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-white">{{ config('app.name', 'Gestión Académica') }}</span>
                            <span class="block truncate text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-300/90">{{ __('Sistema académico') }}</span>
                        </span>
                    </a>

                    <button type="button" @click="sidebarOpen = false"
                            class="ms-auto rounded-lg p-1.5 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden">
                        <x-icon name="close" class="h-5 w-5" />
                        <span class="sr-only">{{ __('Cerrar menú') }}</span>
                    </button>
                </div>

                <nav class="sidebar-scroll flex-1 space-y-6 overflow-y-auto px-3 py-5">
                    <div class="space-y-1">
                        <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-600">{{ __('General') }}</p>

                        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">
                            {{ __('Panel') }}
                        </x-sidebar-link>
                    </div>

                    @if ($usuario->esAdministrador())
                        <div class="space-y-1">
                            <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-600">{{ __('Administración') }}</p>

                            <x-sidebar-link :href="route('admin.usuarios.index')" :active="request()->routeIs('admin.usuarios.*')" icon="users">
                                {{ __('Usuarios') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('admin.facultades.index')" :active="request()->routeIs('admin.facultades.*')" icon="academic-cap">
                                {{ __('Facultades') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('admin.carreras.index')" :active="request()->routeIs('admin.carreras.*')" icon="academic-cap">
                                {{ __('Carreras') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('admin.materias.index')" :active="request()->routeIs('admin.materias.*')" icon="book">
                                {{ __('Materias') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('admin.equivalencias.index')" :active="request()->routeIs('admin.equivalencias.*')" icon="refresh">
                                {{ __('Equivalencias') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('admin.planes.index')" :active="request()->routeIs('admin.planes.*')" icon="document">
                                {{ __('Planes de estudio') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('admin.periodos.index')" :active="request()->routeIs('admin.periodos.*')" icon="calendar">
                                {{ __('Periodos') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('admin.cursos.index')" :active="request()->routeIs('admin.cursos.*')" icon="book">
                                {{ __('Cursos') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('admin.reportes.index')" :active="request()->routeIs('admin.reportes.*')" icon="chart">
                                {{ __('Reportes') }}
                            </x-sidebar-link>
                        </div>
                    @endif

                    @if ($usuario->esDocente())
                        <div class="space-y-1">
                            <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-600">{{ __('Docencia') }}</p>

                            <x-sidebar-link :href="route('docente.cursos.index')" :active="request()->routeIs('docente.cursos.*')" icon="book">
                                {{ __('Mis cursos') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('docente.reportes.index')" :active="request()->routeIs('docente.reportes.*')" icon="chart">
                                {{ __('Reportes') }}
                            </x-sidebar-link>
                        </div>
                    @endif

                    @if ($usuario->esEstudiante())
                        <div class="space-y-1">
                            <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-600">{{ __('Estudiante') }}</p>

                            <x-sidebar-link :href="route('estudiante.cursos.index')" :active="request()->routeIs('estudiante.cursos.*')" icon="sparkles">
                                {{ __('Oferta académica') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('estudiante.inscripciones.index')" :active="request()->routeIs('estudiante.inscripciones.*')" icon="book">
                                {{ __('Mis cursos') }}
                            </x-sidebar-link>
                            <x-sidebar-link :href="route('estudiante.reportes.index')" :active="request()->routeIs('estudiante.reportes.*')" icon="chart">
                                {{ __('Reportes') }}
                            </x-sidebar-link>
                        </div>
                    @endif
                </nav>

                <div class="border-t border-white/10 p-4">
                    <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-500/20 text-sm font-semibold text-brand-200 ring-1 ring-brand-400/30">
                            {{ $iniciales }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ $usuario->name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $rolActual?->etiqueta() ?? __('Sin rol') }}</p>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-lg p-2 text-slate-400 transition hover:bg-white/10 hover:text-white"
                                    title="{{ __('Cerrar sesión') }}">
                                <x-icon name="logout" class="h-5 w-5" />
                                <span class="sr-only">{{ __('Cerrar sesión') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- Contenido --}}
            <div class="flex min-h-screen flex-col lg:pl-72">
                <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-md">
                    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                        <button type="button" @click="sidebarOpen = true"
                                class="-ms-1 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 lg:hidden">
                            <x-icon name="menu" class="h-6 w-6" />
                            <span class="sr-only">{{ __('Abrir menú') }}</span>
                        </button>

                        <div class="min-w-0 flex-1">
                            @isset($header)
                                {{ $header }}
                            @else
                                <h2 class="truncate text-lg font-semibold text-slate-900">{{ config('app.name', 'Gestión Académica') }}</h2>
                            @endisset
                        </div>

                        <a href="{{ route('notificaciones.index') }}"
                           class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                           title="{{ __('Avisos') }}">
                            <x-icon name="bell" class="h-5 w-5" />
                            <span class="sr-only">{{ __('Avisos') }}</span>

                            @if ($sinLeer > 0)
                                <span class="absolute -right-0.5 -top-0.5 inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-brand-500 px-1 py-0.5 text-[10px] font-bold text-ink-950 ring-2 ring-white">
                                    {{ $sinLeer > 9 ? '9+' : $sinLeer }}
                                </span>
                            @endif
                        </a>

                        <x-dropdown align="right" width="56">
                            <x-slot name="trigger">
                                <button type="button"
                                        class="flex items-center gap-2 rounded-full p-0.5 transition hover:ring-2 hover:ring-brand-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-ink-900 text-xs font-semibold text-white">
                                        {{ $iniciales }}
                                    </span>
                                    <span class="hidden text-left sm:block">
                                        <span class="block max-w-[10rem] truncate text-sm font-semibold text-slate-800">{{ $usuario->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $rolActual?->etiqueta() ?? __('Usuario') }}</span>
                                    </span>
                                    <svg class="hidden h-4 w-4 text-slate-400 sm:block" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $usuario->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $usuario->email }}</p>
                                </div>

                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Mi perfil') }}
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('Cerrar sesión') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                <main class="flex-1">
                    @if (session('status') || $errors->any())
                        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                            <x-flash />
                        </div>
                    @endif

                    {{ $slot }}
                </main>

                <footer class="mx-auto w-full max-w-7xl px-4 py-6 text-xs text-slate-400 sm:px-6 lg:px-8">
                    <div class="flex flex-col items-center justify-between gap-1 border-t border-slate-200/70 py-4 sm:flex-row">
                        <p>{{ config('app.name', 'Gestión Académica') }} · {{ __('Programación IV') }}</p>
                        <p>{{ now()->format('d/m/Y') }}</p>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>
