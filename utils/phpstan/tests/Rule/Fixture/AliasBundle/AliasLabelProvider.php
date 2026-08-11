<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AliasBundle;

final class AliasLabelProvider
{
    /**
     * A translation key built out of an entity type, not a service id, yet it looks like a "mautic.%s.%s" format.
     */
    public function provideLabelKey(string $entityType): string
    {
        return "mautic.{$entityType}.{$entityType}";
    }
}
