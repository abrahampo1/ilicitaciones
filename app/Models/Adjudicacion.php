<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjudicacion extends Model
{
    protected $fillable = [
        'licitacion_id',
        'empresa_id',
        'importe',
        'importe_final',
        'urgencia',
        'tipo_procedimiento',
        'descripcion',
        'fecha_adjudicacion',
        'fecha_comienzo',
    ];

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}