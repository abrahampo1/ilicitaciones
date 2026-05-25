<?php

namespace App\Models;

use App\Models\Concerns\HasArticles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasArticles, HasFactory;

    protected $fillable = [
        'nombre',
        'identificador',
    ];

    public function licitaciones()
    {
        return $this->belongsToMany(Licitacion::class, 'adjudicacions')
            ->withPivot('importe', 'importe_final', 'urgencia', 'tipo_procedimiento', 'descripcion', 'fecha_adjudicacion', 'fecha_comienzo')
            ->withTimestamps();
    }
}
