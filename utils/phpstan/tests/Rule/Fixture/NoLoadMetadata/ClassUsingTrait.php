<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NoLoadMetadata;

final class ClassUsingTrait
{
    use TraitWithLoadMetadata;
}
