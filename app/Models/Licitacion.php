<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Licitacion extends Model
{
    protected $fillable = [
        'titulo',
        'descripcion',
        'identificador',
        'estado',
        'importe_total',
        'importe_final',
        'url',
        'id_url',
        'importe_estimado',
        'fecha_contratacion',
        'fecha_actualizacion',
        'categoria_id',
        'organismo_id',
        'datos_raiz',
    ];

    public function organismo()
    {
        return $this->belongsTo(Organismo::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function empresas()
    {
        return $this->belongsToMany(Empresa::class, 'adjudicacions')
            ->withPivot('importe', 'importe_final', 'urgencia', 'tipo_procedimiento', 'descripcion', 'fecha_adjudicacion', 'fecha_comienzo')
            ->withTimestamps();
    }
}
