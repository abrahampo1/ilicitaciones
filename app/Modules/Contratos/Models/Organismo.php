<?php

namespace Modules\Contratos\Models;

use Illuminate\Database\Eloquent\Model;

class Organismo extends Model
{
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
        'dir3_code',
        'organismo_superior',
    ];

    public function licitaciones()
    {
        return $this->hasMany(Licitacion::class);
    }
}
