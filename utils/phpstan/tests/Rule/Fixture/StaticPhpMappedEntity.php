<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\Mapping\ClassMetadata;

class StaticPhpMappedEntity
{
    public static function loadMetadata(ClassMetadata $metadata): void
    {
    }
}
