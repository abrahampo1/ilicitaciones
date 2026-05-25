<?php

namespace App\Models\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Review => 'En revisión',
            self::Published => 'Publicado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'bg-neutral-500/10 text-neutral-400',
            self::Review => 'bg-amber-500/10 text-amber-400',
            self::Published => 'bg-emerald-500/10 text-emerald-400',
        };
    }
}
