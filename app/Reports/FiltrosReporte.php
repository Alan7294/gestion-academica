<?php

namespace App\Reports;

use App\Enums\EstadoInscripcion;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Parámetros compartidos por la vista previa y las descargas de los reportes (RF12).
 */
class FiltrosReporte
{
    /**
     * @var array<string, string>
     */
    private readonly array $etiquetas;

    public function __construct(
        public readonly ?int $periodoId = null,
        public readonly ?int $carreraId = null,
        public readonly ?int $cursoId = null,
        public readonly ?int $docenteId = null,
        public readonly ?string $estado = null,
        public readonly ?float $notaDesde = null,
        public readonly ?float $notaHasta = null,
    ) {
        $this->etiquetas = $this->resolverEtiquetas();
    }

    public static function desdeRequest(Request $request): self
    {
        $validados = Validator::make($request->query(), [
            'periodo_id' => ['nullable', 'integer', 'exists:periodos,id'],
            'carrera_id' => ['nullable', 'integer', 'exists:carreras,id'],
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
            'docente_id' => ['nullable', 'integer', 'exists:users,id'],
            'estado' => ['nullable', 'in:'.implode(',', array_map(
                fn (EstadoInscripcion $estado) => $estado->value,
                EstadoInscripcion::cases(),
            ))],
            'nota_desde' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nota_hasta' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ])->validate();

        $notaDesde = isset($validados['nota_desde']) ? (float) $validados['nota_desde'] : null;
        $notaHasta = isset($validados['nota_hasta']) ? (float) $validados['nota_hasta'] : null;

        if ($notaDesde !== null && $notaHasta !== null && $notaDesde > $notaHasta) {
            throw ValidationException::withMessages([
                'nota_desde' => 'La nota mínima no puede ser mayor que la nota máxima.',
            ]);
        }

        return new self(
            periodoId: self::entero($validados, 'periodo_id'),
            carreraId: self::entero($validados, 'carrera_id'),
            cursoId: self::entero($validados, 'curso_id'),
            docenteId: self::entero($validados, 'docente_id'),
            estado: isset($validados['estado']) ? (string) $validados['estado'] : null,
            notaDesde: $notaDesde,
            notaHasta: $notaHasta,
        );
    }

    public function aplicarACursos(Builder $query): Builder
    {
        if ($this->periodoId !== null) {
            $query->where('periodo_id', $this->periodoId);
        }

        if ($this->carreraId !== null) {
            $query->where('carrera_id', $this->carreraId);
        }

        if ($this->cursoId !== null) {
            $query->where('id', $this->cursoId);
        }

        if ($this->docenteId !== null) {
            $query->where('docente_id', $this->docenteId);
        }

        return $query;
    }

    public function aplicarAInscripciones(Builder $query, ?string $estadoPorDefecto = null): Builder
    {
        if ($this->tieneFiltrosDeCurso()) {
            $query->whereHas('curso', fn (Builder $curso) => $this->aplicarACursos($curso));
        }

        $estado = $this->estado ?? $estadoPorDefecto;

        if ($estado !== null) {
            $query->where('estado', $estado);
        }

        if ($this->notaDesde !== null) {
            $query->where('nota_final', '>=', $this->notaDesde);
        }

        if ($this->notaHasta !== null) {
            $query->where('nota_final', '<=', $this->notaHasta);
        }

        return $query;
    }

    public function tieneFiltrosDeCurso(): bool
    {
        return $this->periodoId !== null
            || $this->carreraId !== null
            || $this->cursoId !== null
            || $this->docenteId !== null;
    }

    public function activos(): bool
    {
        return $this->tieneFiltrosDeCurso()
            || $this->estado !== null
            || $this->notaDesde !== null
            || $this->notaHasta !== null;
    }

    /**
     * @return array<string, string>
     */
    public function contexto(): array
    {
        return $this->etiquetas;
    }

    /**
     * @return array<string, int|float|string>
     */
    public function query(): array
    {
        return array_filter([
            'periodo_id' => $this->periodoId,
            'carrera_id' => $this->carreraId,
            'curso_id' => $this->cursoId,
            'docente_id' => $this->docenteId,
            'estado' => $this->estado,
            'nota_desde' => $this->notaDesde,
            'nota_hasta' => $this->notaHasta,
        ], fn (int|float|string|null $valor) => $valor !== null);
    }

    /**
     * @param  array<string, mixed>  $validados
     */
    private static function entero(array $validados, string $clave): ?int
    {
        return isset($validados[$clave]) ? (int) $validados[$clave] : null;
    }

    /**
     * @return array<string, string>
     */
    private function resolverEtiquetas(): array
    {
        $etiquetas = [];

        if ($this->periodoId !== null) {
            $etiquetas['Filtro: Periodo'] = Periodo::find($this->periodoId)?->nombre ?? '—';
        }

        if ($this->carreraId !== null) {
            $etiquetas['Filtro: Carrera'] = Carrera::find($this->carreraId)?->nombre ?? '—';
        }

        if ($this->cursoId !== null) {
            $curso = Curso::find($this->cursoId);
            $etiquetas['Filtro: Materia'] = $curso === null ? '—' : "{$curso->codigo} - {$curso->nombre}";
        }

        if ($this->docenteId !== null) {
            $etiquetas['Filtro: Docente'] = User::find($this->docenteId)?->name ?? '—';
        }

        if ($this->estado !== null) {
            $etiquetas['Filtro: Estado'] = EstadoInscripcion::tryFrom($this->estado)?->etiqueta() ?? $this->estado;
        }

        if ($this->notaDesde !== null || $this->notaHasta !== null) {
            $desde = $this->notaDesde === null ? '0,00' : number_format($this->notaDesde, 2, ',', '.');
            $hasta = $this->notaHasta === null ? '100,00' : number_format($this->notaHasta, 2, ',', '.');
            $etiquetas['Filtro: Nota final'] = "{$desde} - {$hasta}";
        }

        return $etiquetas;
    }
}
