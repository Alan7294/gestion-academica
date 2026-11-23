<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Nuevo plan de estudios') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.planes.store') }}"
                  class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6">
                @csrf

                @include('admin.planes._form', ['plan' => null])

                <div class="mt-6 flex items-center gap-3">
                    <x-primary-button>{{ __('Crear plan') }}</x-primary-button>
                    <a href="{{ route('admin.planes.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                        {{ __('Cancelar') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
