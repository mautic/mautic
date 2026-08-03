<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

// not a controller - must be skipped
final class GetModelByStringService
{
    public function run(): void
    {
        $this->getModel('lead');
    }

    public function getModel(string $modelNameKey): object
    {
        return new \stdClass();
    }
}
