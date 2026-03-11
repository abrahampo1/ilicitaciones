<?php

namespace Modules\Presupuestos\Models;

use Illuminate\Database\Eloquent\Model;

class ClasificacionPresupuestaria extends Model
{
    protected $table = 'clasificaciones_presupuestarias';

    protected $fillable = [
        'tipo',
        'codigo',
        'codigo_padre',
        'nivel',
        'nombre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'nivel' => 'integer',
        ];
    }

    public const TIPO_ORGANICA = 'organica';
    public const TIPO_FUNCIONAL = 'funcional';
    public const TIPO_ECONOMICA = 'economica';

    public const CAPITULOS_GASTOS = [
        '1' => 'Gastos de personal',
        '2' => 'Gastos corrientes en bienes y servicios',
        '3' => 'Gastos financieros',
        '4' => 'Transferencias corrientes',
        '5' => 'Fondo de contingencia',
        '6' => 'Inversiones reales',
        '7' => 'Transferencias de capital',
        '8' => 'Activos financieros',
        '9' => 'Pasivos financieros',
    ];

    public const CAPITULOS_INGRESOS = [
        '1' => 'Impuestos directos',
        '2' => 'Impuestos indirectos',
        '3' => 'Tasas, precios públicos y otros ingresos',
        '4' => 'Transferencias corrientes',
        '5' => 'Ingresos patrimoniales',
        '6' => 'Enajenación de inversiones reales',
        '7' => 'Transferencias de capital',
        '8' => 'Activos financieros',
        '9' => 'Pasivos financieros',
    ];

    // Scopes
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeNivel($query, int $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    public function scopeHijos($query, string $codigoPadre)
    {
        return $query->where('codigo_padre', $codigoPadre);
    }

    // Relationships
    public function hijos()
    {
        return $this->hasMany(self::class, 'codigo_padre', 'codigo')
            ->where('tipo', $this->tipo);
    }

    public function padre()
    {
        return $this->belongsTo(self::class, 'codigo_padre', 'codigo')
            ->where('tipo', $this->tipo);
    }

    public function partidasOrganica()
    {
        return $this->hasMany(PartidaPresupuestaria::class, 'clasificacion_organica_id');
    }

    public function partidasFuncional()
    {
        return $this->hasMany(PartidaPresupuestaria::class, 'clasificacion_funcional_id');
    }

    public function partidasEconomica()
    {
        return $this->hasMany(PartidaPresupuestaria::class, 'clasificacion_economica_id');
    }
}
