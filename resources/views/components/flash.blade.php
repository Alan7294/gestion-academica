@if (session('status'))
    <div class="mb-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm" role="status">
        <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
        <p>{{ session('status') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm" role="alert">
        <x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
        <div>
            <p class="font-semibold">{{ __('Revisa los siguientes errores:') }}</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
