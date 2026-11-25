<?php

namespace App\Models;

use Database\Factories\CertificadoDocenteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['titulo', 'institucion', 'codigo'])]
class CertificadoDocente extends Model
{
    /** @use HasFactory<CertificadoDocenteFactory> */
    use HasFactory;

    protected $table = 'certificados_docentes';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
