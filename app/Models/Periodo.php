<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'fecha_inicio', 'fecha_fin', 'activo'])]
class Periodo extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    public function scopeActivo(Builder $query): void
    {
        $query->where('activo', true);
    }

    public static function actual(): ?self
    {
        return static::activo()->orderByDesc('fecha_inicio')->first();
    }
}
