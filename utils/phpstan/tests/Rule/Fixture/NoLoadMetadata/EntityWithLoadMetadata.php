<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NoLoadMetadata;

use Doctrine\ORM\Mapping\ClassMetadata;

final class EntityWithLoadMetadata
{
    public static function loadMetadata(ClassMetadata $metadata): void
    {
    }
}
