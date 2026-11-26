<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Nueva materia') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6">
                <form method="POST" action="{{ route('admin.materias.store') }}">
                    @csrf

                    @include('admin.materias._form', ['creando' => true])

                    <div class="mt-6 flex items-center gap-3">
                        <x-primary-button>{{ __('Guardar') }}</x-primary-button>
                        <a href="{{ route('admin.materias.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                            {{ __('Cancelar') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
