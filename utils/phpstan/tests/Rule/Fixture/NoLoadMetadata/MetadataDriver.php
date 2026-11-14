<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NoLoadMetadata;

final class MetadataDriver
{
    public function loadMetadata(string $className): void
    {
    }
}
