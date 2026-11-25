<?php

namespace App\Models;

use Database\Factories\FacultadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'activo'])]
class Facultad extends Model
{
    /** @use HasFactory<FacultadFactory> */
    use HasFactory;

    protected $table = 'facultades';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class);
    }
}
