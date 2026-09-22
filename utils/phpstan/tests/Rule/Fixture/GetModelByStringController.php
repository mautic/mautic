<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

// the model is fetched by string - must be reported
final class GetModelByStringController
{
    public function indexAction(): void
    {
        $this->getModel('lead');
    }

    public function otherAction(string $modelName): void
    {
        // dynamic name is not known, nothing to inject
        $this->getModel($modelName);
    }

    public function getModel(string $modelNameKey): object
    {
        return new \stdClass();
    }
}
