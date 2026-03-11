<?php

namespace Modules\Contratos\Models;

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
        // Campos enriquecidos de gobtracker
        'expediente',
        'status_code',
        'tipo_contrato_code',
        'subtipo_contrato_code',
        'procedimiento_code',
        'urgencia_code',
        'importe_sin_iva',
        'importe_con_iva',
        'valor_estimado',
        'cpv_codes',
        'comunidad_autonoma',
        'nuts_code',
        'lugar_ejecucion',
        'duracion',
        'duracion_unidad',
        'adjudicatario_nombre',
        'adjudicatario_nif',
        'importe_adjudicacion_sin_iva',
        'importe_adjudicacion_con_iva',
        'fecha_presentacion_limite',
        'fecha_inicio',
        'fecha_fin',
        'fecha_adjudicacion',
        'fecha_formalizacion',
        'resultado_code',
        'num_ofertas',
        'external_id',
        'link',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'cpv_codes' => 'array',
            'importe_sin_iva' => 'decimal:2',
            'importe_con_iva' => 'decimal:2',
            'valor_estimado' => 'decimal:2',
            'importe_adjudicacion_sin_iva' => 'decimal:2',
            'importe_adjudicacion_con_iva' => 'decimal:2',
            'duracion' => 'decimal:2',
            'fecha_presentacion_limite' => 'date',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_adjudicacion' => 'date',
            'fecha_formalizacion' => 'date',
            'synced_at' => 'datetime',
        ];
    }

    // Constantes de estado
    public const STATUS_PREVIO = 'PRE';
    public const STATUS_EN_PLAZO = 'PUB';
    public const STATUS_EVALUACION = 'EV';
    public const STATUS_ADJUDICADA = 'ADJ';
    public const STATUS_RESUELTA = 'RES';
    public const STATUS_ANULADA = 'ANUL';

    public const STATUS_LABELS = [
        'PRE' => 'Anuncio previo',
        'PUB' => 'En plazo',
        'EV' => 'Pendiente de adjudicación',
        'ADJ' => 'Adjudicada',
        'RES' => 'Resuelta',
        'ANUL' => 'Anulada',
    ];

    public const TIPO_LABELS = [
        '1' => 'Obras',
        '2' => 'Servicios',
        '3' => 'Suministros',
        '7' => 'Gestión de servicios públicos',
        '8' => 'Concesión de obras',
        '21' => 'Concesión de servicios',
        '31' => 'Colaboración público-privada',
        '40' => 'Administrativo especial',
        '50' => 'Privado',
    ];

    public const PROCEDIMIENTO_LABELS = [
        '1' => 'Abierto',
        '2' => 'Restringido',
        '3' => 'Negociado sin publicidad',
        '4' => 'Negociado con publicidad',
        '5' => 'Diálogo competitivo',
        '6' => 'Abierto simplificado',
        '100' => 'Basado en acuerdo marco',
        '999' => 'Otros',
    ];

    // Scopes
    public function scopeStatus($query, string $status)
    {
        return $query->where('status_code', $status);
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo_contrato_code', $tipo);
    }

    public function scopeProcedimiento($query, string $proc)
    {
        return $query->where('procedimiento_code', $proc);
    }

    public function scopeImporteMin($query, float $min)
    {
        return $query->where('importe_con_iva', '>=', $min);
    }

    public function scopeImporteMax($query, float $max)
    {
        return $query->where('importe_con_iva', '<=', $max);
    }

    // Helper para obtener label de estado
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status_code] ?? ($this->estado ?? 'Desconocido');
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPO_LABELS[$this->tipo_contrato_code] ?? 'Otro';
    }

    public function getProcedimientoLabelAttribute(): string
    {
        return self::PROCEDIMIENTO_LABELS[$this->procedimiento_code] ?? 'Otro';
    }

    // Relationships
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
