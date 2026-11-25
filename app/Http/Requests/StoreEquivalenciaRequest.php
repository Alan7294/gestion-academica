<?php

namespace App\Http\Requests;

use App\Models\Materia;
use App\Models\MateriaEquivalencia;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquivalenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $carreraDestinoId = (int) $this->input('carrera_destino_id');
        $carreraOrigenId = (int) $this->input('carrera_origen_id');
        $materiaId = (int) $this->input('materia_id');

        $perteneceACarrera = fn (int $materiaId, int $carreraId): bool => Materia::whereKey($materiaId)
            ->whereHas('planesEstudio', fn ($query) => $query
                ->where('planes_estudio.carrera_id', $carreraId))
            ->exists();

        return [
            'carrera_destino_id' => ['required', 'integer', Rule::exists('carreras', 'id')],
            'carrera_origen_id' => [
                'required',
                'integer',
                Rule::exists('carreras', 'id'),
                'different:carrera_destino_id',
            ],
            'materia_id' => [
                'required',
                'integer',
                Rule::exists('materias', 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($carreraDestinoId, $perteneceACarrera): void {
                    if ($carreraDestinoId === 0) {
                        return;
                    }

                    if (! $perteneceACarrera((int) $value, $carreraDestinoId)) {
                        $fail('La materia destino debe pertenecer a un plan de la carrera destino seleccionada.');
                    }
                },
            ],
            'equivalente_id' => [
                'required',
                'integer',
                Rule::exists('materias', 'id'),
                Rule::unique('materia_equivalencias', 'equivalente_id')
                    ->where(fn ($query) => $query->where('materia_id', $materiaId)),
                function (string $attribute, mixed $value, Closure $fail) use ($carreraOrigenId, $materiaId, $perteneceACarrera): void {
                    $equivalente = Materia::find($value);

                    if ($equivalente === null) {
                        return;
                    }

                    if ($carreraOrigenId !== 0 && ! $perteneceACarrera((int) $value, $carreraOrigenId)) {
                        $fail('La materia origen debe pertenecer a un plan de la carrera origen seleccionada.');
                    }

                    if ($equivalente->id === $materiaId) {
                        $fail('La materia no puede equivaler a sí misma.');
                    }

                    if ($this->boolean('bilateral')
                        && MateriaEquivalencia::where('materia_id', $value)->where('equivalente_id', $materiaId)->exists()) {
                        $fail('La equivalencia inversa ya está registrada.');
                    }
                },
            ],
            'bilateral' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'carrera_destino_id.required' => 'Selecciona la carrera destino.',
            'carrera_origen_id.required' => 'Selecciona la carrera origen.',
            'carrera_origen_id.different' => 'La carrera origen debe ser distinta de la carrera destino.',
            'materia_id.required' => 'Selecciona la materia destino.',
            'equivalente_id.required' => 'Selecciona la materia origen.',
            'equivalente_id.unique' => 'Esta equivalencia ya está registrada para la materia.',
        ];
    }
}
