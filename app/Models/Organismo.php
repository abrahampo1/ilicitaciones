<?php

namespace App\Models;

use App\Models\Concerns\HasArticles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organismo extends Model
{
    use HasArticles, HasFactory;

    protected $fillable = [
        'nombre',
        'identificador',
        'direccion',
        'codigo_postal',
        'provincia',
        'pais',
        'contacto_nombre',
        'contacto_telefono',
        'contacto_fax',
        'contacto_email',
        'sitio_web',
    ];

    public function licitaciones()
    {
        return $this->hasMany(Licitacion::class);
    }
}
