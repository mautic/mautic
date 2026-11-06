<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ParentConstructor;

final class OwnConstructorChildService extends ParentService
{
    public function __construct(
        private readonly \SplStack $own,
    ) {
        parent::__construct(new \stdClass(), new \ArrayObject());
    }
}
