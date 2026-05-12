<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

class UuidHelper
{
    /**
     * Validates if a string is a valid UUID (v1-v5).
     */
    public static function isValidUuid(string $uuid): bool
    {
        return 1 === preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }
}
