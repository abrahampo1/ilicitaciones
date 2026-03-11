<?php

namespace Modules\Presupuestos\Models;

use Illuminate\Database\Eloquent\Model;

class EjecucionPresupuestaria extends Model
{
    protected $table = 'ejecucion_presupuestaria';

    protected $fillable = [
        'partida_id',
        'periodo',
        'credito_autorizado',
        'credito_dispuesto',
        'obligaciones',
        'pagos_propuestos',
        'pagos_realizados',
        'remanentes',
        'porcentaje_ejecucion',
        'fuente',
    ];

    protected function casts(): array
    {
        return [
            'credito_autorizado' => 'decimal:2',
            'credito_dispuesto' => 'decimal:2',
            'obligaciones' => 'decimal:2',
            'pagos_propuestos' => 'decimal:2',
            'pagos_realizados' => 'decimal:2',
            'remanentes' => 'decimal:2',
            'porcentaje_ejecucion' => 'decimal:2',
        ];
    }

    // Scopes
    public function scopePeriodo($query, string $periodo)
    {
        return $query->where('periodo', $periodo);
    }

    public function scopeAnual($query)
    {
        return $query->where('periodo', 'anual');
    }

    // Relationships
    public function partida()
    {
        return $this->belongsTo(PartidaPresupuestaria::class, 'partida_id');
    }
}
