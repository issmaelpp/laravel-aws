<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::InReview => 'En revisión',
            self::Published => 'Publicado',
            self::Archived => 'Archivado',
        };
    }

    /**
     * Determine if the article is published.
     */
    public function isPublished(): bool
    {
        return $this === self::Published;
    }
}
