<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

abstract class AbstractUnusedParameterController
{
    // a child can override this with a body that does use the parameter, so the base is left alone
    public function indexAction(string $unused): string
    {
        return 'index';
    }
}
