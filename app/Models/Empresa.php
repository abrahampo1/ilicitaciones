<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = [
        'nombre',
        'identificador'
    ];

    public function licitaciones()
    {
        return $this->belongsToMany(Licitacion::class, 'adjudicacions')
            ->withPivot('importe', 'importe_final', 'urgencia', 'tipo_procedimiento', 'descripcion', 'fecha_adjudicacion', 'fecha_comienzo')
            ->withTimestamps();
    }
}
