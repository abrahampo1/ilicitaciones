<?php

namespace Modules\Contratos\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = [
        'nombre',
        'xml_id',
    ];
}
