<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

trait GetModelByStringTrait
{
    public function indexAction(): void
    {
        $this->getModel('lead');
    }

    public function getModel(string $modelNameKey): object
    {
        return new \stdClass();
    }
}
