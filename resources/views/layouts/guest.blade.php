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
        <div class="grid min-h-screen lg:grid-cols-2">
            {{-- Panel de marca --}}
            <aside class="relative hidden overflow-hidden bg-ink-950 px-12 py-14 lg:flex lg:flex-col lg:justify-between">
                <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                    <div class="absolute -left-24 -top-24 h-80 w-80 rounded-full bg-brand-500/20 blur-3xl"></div>
                    <div class="absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-brand-400/10 blur-3xl"></div>
                    <div class="absolute inset-0 opacity-[0.06]"
                         style="background-image: linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px); background-size: 48px 48px;"></div>
                </div>

                <div class="relative">
                    <a href="/" class="inline-flex items-center gap-3">
                        <x-application-logo class="h-11 w-11" />
                        <span>
                            <span class="block text-base font-semibold text-white">{{ config('app.name', 'Gestión Académica') }}</span>
                            <span class="block text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-300/90">{{ __('Sistema académico') }}</span>
                        </span>
                    </a>

                    <h1 class="mt-16 max-w-md text-4xl font-semibold leading-tight text-white">
                        {{ __('Todo tu proceso académico,') }}
                        <span class="text-brand-300">{{ __('en un solo lugar.') }}</span>
                    </h1>

                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-400">
                        {{ __('Inscripciones, calificaciones, horarios y reportes para administradores, docentes y estudiantes.') }}
                    </p>

                    <ul class="mt-10 space-y-4">
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500/15 text-brand-300 ring-1 ring-brand-400/20">
                                <x-icon name="book" class="h-4 w-4" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ __('Inscripción en línea') }}</p>
                                <p class="text-xs text-slate-400">{{ __('Oferta académica por carrera y periodo, con control de cupos.') }}</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500/15 text-brand-300 ring-1 ring-brand-400/20">
                                <x-icon name="chart" class="h-4 w-4" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ __('Calificaciones al día') }}</p>
                                <p class="text-xs text-slate-400">{{ __('Promedios ponderados y avisos automáticos en cada evaluación.') }}</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500/15 text-brand-300 ring-1 ring-brand-400/20">
                                <x-icon name="download" class="h-4 w-4" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ __('Reportes PDF y Excel') }}</p>
                                <p class="text-xs text-slate-400">{{ __('Historial académico, notas por curso y carga docente.') }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <p class="relative text-xs text-slate-500">
                    {{ __('Proyecto Programación IV · Gestión Académica') }}
                </p>
            </aside>

            {{-- Formulario --}}
            <main class="flex items-center justify-center px-6 py-12 sm:px-10">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <x-application-logo class="h-11 w-11" />
                        <div>
                            <p class="text-base font-semibold text-slate-900">{{ config('app.name', 'Gestión Académica') }}</p>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Sistema académico') }}</p>
                        </div>
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
