<?php

namespace App\Models\Enums;

enum ArticleSection: string
{
    case Rankings = 'rankings';
    case Alertas = 'alertas';
    case Informes = 'informes';
    case Perfiles = 'perfiles';

    public function label(): string
    {
        return match ($this) {
            self::Rankings => 'Rankings',
            self::Alertas => 'Alertas',
            self::Informes => 'Informes',
            self::Perfiles => 'Perfiles',
        };
    }

    /** Clases Tailwind para el badge (mismo idioma visual que los estados del home). */
    public function color(): string
    {
        return match ($this) {
            self::Rankings => 'bg-emerald-500/10 text-emerald-400',
            self::Alertas => 'bg-amber-500/10 text-amber-400',
            self::Informes => 'bg-cyan-500/10 text-cyan-400',
            self::Perfiles => 'bg-sky-500/10 text-sky-400',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Rankings => 'Quién gana y quién gasta más en la contratación pública.',
            self::Alertas => 'Patrones que merecen atención: concentración, urgencia, adjudicaciones sin concurrencia.',
            self::Informes => 'Radiografías del gasto público por sector y territorio.',
            self::Perfiles => 'Investigaciones en profundidad sobre empresas y organismos.',
        };
    }
}
