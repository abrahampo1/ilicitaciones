<?php

namespace Modules\Presupuestos\Models;

use Illuminate\Database\Eloquent\Model;

class EntidadPresupuestaria extends Model
{
    protected $table = 'entidades_presupuestarias';

    protected $fillable = [
        'tipo',
        'nombre',
        'codigo_ine',
        'codigo_ccaa',
        'provincia',
        'poblacion',
        'codigo_dir3',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'poblacion' => 'integer',
        ];
    }

    public const TIPO_ESTADO = 'estado';
    public const TIPO_CCAA = 'ccaa';
    public const TIPO_MUNICIPIO = 'municipio';

    public const TIPO_LABELS = [
        'estado' => 'Estado',
        'ccaa' => 'Comunidad Autónoma',
        'municipio' => 'Municipio',
    ];

    public const CCAA_CODES = [
        '01' => 'Andalucía',
        '02' => 'Aragón',
        '03' => 'Asturias',
        '04' => 'Illes Balears',
        '05' => 'Canarias',
        '06' => 'Cantabria',
        '07' => 'Castilla y León',
        '08' => 'Castilla-La Mancha',
        '09' => 'Cataluña',
        '10' => 'Comunitat Valenciana',
        '11' => 'Extremadura',
        '12' => 'Galicia',
        '13' => 'Comunidad de Madrid',
        '14' => 'Región de Murcia',
        '15' => 'Comunidad Foral de Navarra',
        '16' => 'País Vasco',
        '17' => 'La Rioja',
        '18' => 'Ceuta',
        '19' => 'Melilla',
    ];

    // Scopes
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeCcaa($query, string $codigoCcaa)
    {
        return $query->where('codigo_ccaa', $codigoCcaa);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('nombre', 'like', "%{$term}%");
    }

    // Relationships
    public function partidas()
    {
        return $this->hasMany(PartidaPresupuestaria::class, 'entidad_id');
    }

    // Helpers
    public function getTipoLabelAttribute(): string
    {
        return self::TIPO_LABELS[$this->tipo] ?? $this->tipo;
    }

    public function getCcaaLabelAttribute(): ?string
    {
        return $this->codigo_ccaa ? (self::CCAA_CODES[$this->codigo_ccaa] ?? null) : null;
    }
}
