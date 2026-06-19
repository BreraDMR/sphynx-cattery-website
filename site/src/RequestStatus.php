<?php

declare(strict_types=1);

namespace App;

/**
 * Status codes are stored in English (a common pattern: code values stay
 * stable and locale-independent, UI labels are translated separately) --
 * see ukrainianLabel() for what the admin panel actually shows.
 */
final class RequestStatus
{
    public const NEW = 'new';
    public const IN_PROGRESS = 'in_progress';
    public const CLOSED = 'closed';

    /** @return string[] */
    public static function all(): array
    {
        return [self::NEW, self::IN_PROGRESS, self::CLOSED];
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::all(), true);
    }

    public static function ukrainianLabel(string $status): string
    {
        return match ($status) {
            self::NEW => 'Нова',
            self::IN_PROGRESS => 'В роботі',
            self::CLOSED => 'Закрита',
            default => $status,
        };
    }
}
