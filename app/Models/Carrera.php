<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo', 'nombre', 'facultad', 'activo'])]
class Carrera extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }
}
