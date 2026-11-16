<?php

namespace App\Enums;

enum ArticleVisibility: string
{
    case Community = 'community';
    case Public = 'public';

    /**
     * Get the display label for the visibility.
     */
    public function label(): string
    {
        return match ($this) {
            self::Community => 'Comunidad',
            self::Public => 'Público',
        };
    }

    /**
     * Determine if the article is public.
     */
    public function isPublic(): bool
    {
        return $this === self::Public;
    }
}
