<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Gestión Académica') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-ink-950 font-sans text-slate-300 antialiased">
        <div class="relative flex min-h-screen flex-col overflow-hidden">
            {{-- Fondos decorativos --}}
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-brand-500/20 blur-3xl"></div>
                <div class="absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-brand-400/10 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[0.05]"
                     style="background-image: linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px); background-size: 56px 56px;"></div>
            </div>

            <header class="relative mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
                <a href="/" class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-10" />
                    <span>
                        <span class="block text-sm font-semibold text-white">{{ config('app.name', 'Gestión Académica') }}</span>
                        <span class="block text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-300/90">{{ __('Sistema académico') }}</span>
                    </span>
                </a>

                <nav class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-ink-950 transition hover:bg-brand-400">
                            {{ __('Ir al panel') }}
                            <x-icon name="arrow-right" class="h-4 w-4" />
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-300 transition hover:bg-white/5 hover:text-white">
                            {{ __('Iniciar sesión') }}
                        </a>

                    @endauth
                </nav>
            </header>

            <main class="relative mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center px-6 py-16">
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-brand-400/30 bg-brand-500/10 px-3 py-1 text-xs font-semibold text-brand-200">
                    <x-icon name="sparkles" class="h-3.5 w-3.5" />
                    {{ __('Proyecto Programación IV') }}
                </span>

                <h1 class="mt-6 max-w-3xl text-4xl font-semibold leading-tight text-white sm:text-5xl">
                    {{ __('Gestión académica simple,') }}
                    <span class="text-brand-300">{{ __('para toda la comunidad.') }}</span>
                </h1>

                <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-400">
                    {{ __('Administra carreras, periodos, cursos y horarios; inscribe estudiantes, registra calificaciones y genera reportes en PDF y Excel.') }}
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-semibold text-ink-950 shadow-glow transition hover:bg-brand-400">
                        {{ auth()->check() ? __('Ir al panel') : __('Comenzar') }}
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>

                    @guest
                        <p class="text-sm text-slate-400">
                            {{ __('Las cuentas las crea el administrador del sistema.') }}
                        </p>
                    @endguest
                </div>

                <div class="mt-16 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500/15 text-brand-300">
                            <x-icon name="users" class="h-5 w-5" />
                        </span>
                        <h2 class="mt-3 text-sm font-semibold text-white">{{ __('Roles y permisos') }}</h2>
                        <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ __('Administradores, docentes y estudiantes con acceso a lo que les corresponde.') }}</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500/15 text-brand-300">
                            <x-icon name="calendar" class="h-5 w-5" />
                        </span>
                        <h2 class="mt-3 text-sm font-semibold text-white">{{ __('Horarios sin choques') }}</h2>
                        <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ __('El sistema impide programar a un docente en dos clases a la misma hora.') }}</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500/15 text-brand-300">
                            <x-icon name="chart" class="h-5 w-5" />
                        </span>
                        <h2 class="mt-3 text-sm font-semibold text-white">{{ __('Reportes al instante') }}</h2>
                        <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ __('Historial académico y notas por curso en PDF y Excel.') }}</p>
                    </div>
                </div>
            </main>

            <footer class="relative mx-auto w-full max-w-6xl px-6 pb-8 text-xs text-slate-600">
                {{ config('app.name', 'Gestión Académica') }} · {{ now()->year }}
            </footer>
        </div>
    </body>
</html>
