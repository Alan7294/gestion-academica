@if (session('status'))
    <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-800" role="status">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-800" role="alert">
        <p class="font-medium">{{ __('Revisa los siguientes errores:') }}</p>
        <ul class="mt-2 list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
