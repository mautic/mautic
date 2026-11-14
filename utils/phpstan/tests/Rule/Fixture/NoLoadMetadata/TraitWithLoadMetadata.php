<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NoLoadMetadata;

use Doctrine\ORM\Mapping\ClassMetadata;

trait TraitWithLoadMetadata
{
    public static function loadMetadata(ClassMetadata $metadata): void
    {
    }
}
