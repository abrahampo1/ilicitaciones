<?php

return [
    // Email de contacto para envío de información / avisos editoriales.
    'contacto' => env('PERIODICO_CONTACTO', 'ilicitaciones@pm.me'),

    // Detectores activos (clave = tipo de StoryCandidate).
    'detectores_activos' => [
        'adjudicatario_unico',
        'concentracion',
        'urgencia',
        'sobrecoste',
        'sin_competencia',
        'ranking',
        'informe_sectorial',
        'informe_regional',
        'perfil',
    ],

    // Umbrales de cada detector (bajables en tests vía config).
    'umbrales' => [
        'adjudicatario_unico_importe' => 1_000_000,

        'concentracion_share' => 0.60,
        'concentracion_volumen_min' => 2_000_000,
        'concentracion_min_contratos' => 3,

        'urgencia_ratio' => 0.40,
        'urgencia_min_total' => 10,
        'urgencia_codigos' => ['2', '3'], // urgente / emergencia (CODICE)

        'sobrecoste_pct' => 0.20,
        'sobrecoste_base_min' => 500_000,

        'sin_competencia_codigos' => ['3', '6'], // negociado sin publicidad / adjudicación directa
        'sin_competencia_importe' => 1_000_000,

        'ranking_volumen_min' => 1_000_000,
        'informe_volumen_min' => 1_000_000,
        'perfil_importe_min' => 5_000_000,
        'perfil_min_alertas' => 2, // entidad con >=N alertas concurrentes => merece perfil
    ],

    // Ventanas temporales.
    'ventana_dias' => 30,
    'ventana_meses' => 12,

    // Control de generación con IA (coste).
    'generacion' => [
        'min_score' => 40,       // solo lo más noticiable se redacta
        'cap_diario' => 15,      // tope de borradores/día (llamadas API)
        'limit_por_run' => 15,
        'confidence_min' => 0.5, // por debajo => queda pendiente para humano
    ],

    // Enfriamiento por sección (días) antes de poder regenerar una historia.
    'cooldown_dias' => [
        'rankings' => 30,
        'alertas' => 90,
        'informes' => 30,
        'perfiles' => 180,
    ],
];
