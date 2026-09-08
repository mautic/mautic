<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\Mapping\ClassMetadata;

readonly class ReadonlyEntity
{
    public static function loadMetadata(ClassMetadata $metadata): void
    {
    }
}
