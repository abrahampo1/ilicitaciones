<?php

namespace Modules\Presupuestos\Models;

use Illuminate\Database\Eloquent\Model;

class PartidaPresupuestaria extends Model
{
    protected $table = 'partidas_presupuestarias';

    protected $fillable = [
        'entidad_id',
        'ejercicio',
        'tipo_presupuesto',
        'clasificacion_organica_id',
        'clasificacion_funcional_id',
        'clasificacion_economica_id',
        'codigo_organica',
        'codigo_funcional',
        'codigo_economica',
        'credito_inicial',
        'credito_definitivo',
        'credito_actual',
        'fuente',
        'external_id',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'ejercicio' => 'integer',
            'credito_inicial' => 'decimal:2',
            'credito_definitivo' => 'decimal:2',
            'credito_actual' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }

    public const FUENTE_PGE = 'pge';
    public const FUENTE_GOBIERTO = 'gobierto';
    public const FUENTE_CONPREL = 'conprel';
    public const FUENTE_CCAA = 'ccaa';

    public const TIPO_GASTOS = 'gastos';
    public const TIPO_INGRESOS = 'ingresos';

    public const FUENTE_LABELS = [
        'pge' => 'Presupuestos Generales del Estado',
        'gobierto' => 'Gobierto (municipales)',
        'conprel' => 'CONPREL',
        'ccaa' => 'Comunidades Autónomas',
    ];

    // Scopes
    public function scopeEjercicio($query, int $year)
    {
        return $query->where('ejercicio', $year);
    }

    public function scopeGastos($query)
    {
        return $query->where('tipo_presupuesto', self::TIPO_GASTOS);
    }

    public function scopeIngresos($query)
    {
        return $query->where('tipo_presupuesto', self::TIPO_INGRESOS);
    }

    public function scopeFuente($query, string $fuente)
    {
        return $query->where('fuente', $fuente);
    }

    public function scopeCapitulo($query, string $capitulo)
    {
        return $query->where('codigo_economica', 'like', "{$capitulo}%");
    }

    // Relationships
    public function entidad()
    {
        return $this->belongsTo(EntidadPresupuestaria::class, 'entidad_id');
    }

    public function clasificacionOrganica()
    {
        return $this->belongsTo(ClasificacionPresupuestaria::class, 'clasificacion_organica_id');
    }

    public function clasificacionFuncional()
    {
        return $this->belongsTo(ClasificacionPresupuestaria::class, 'clasificacion_funcional_id');
    }

    public function clasificacionEconomica()
    {
        return $this->belongsTo(ClasificacionPresupuestaria::class, 'clasificacion_economica_id');
    }

    public function ejecuciones()
    {
        return $this->hasMany(EjecucionPresupuestaria::class, 'partida_id');
    }

    // Helpers
    public function getCreditoEfectivoAttribute(): float
    {
        return (float) ($this->credito_actual ?? $this->credito_definitivo ?? $this->credito_inicial ?? 0);
    }
}
