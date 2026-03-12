<?php

namespace Modules\Contratos\Models;

use Illuminate\Database\Eloquent\Model;

class LicitacionHistorico extends Model
{
    protected $table = 'licitacion_historicos';

    protected $fillable = [
        'licitacion_id',
        'estado',
        'status_code',
        'importe_con_iva',
        'importe_sin_iva',
        'valor_estimado',
        'importe_adjudicacion_sin_iva',
        'importe_adjudicacion_con_iva',
        'fecha_actualizacion',
        'datos_raiz',
        'cambios',
    ];

    protected function casts(): array
    {
        return [
            'datos_raiz' => 'array',
            'cambios' => 'array',
            'importe_con_iva' => 'decimal:2',
            'importe_sin_iva' => 'decimal:2',
            'valor_estimado' => 'decimal:2',
            'importe_adjudicacion_sin_iva' => 'decimal:2',
            'importe_adjudicacion_con_iva' => 'decimal:2',
        ];
    }

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }
}
