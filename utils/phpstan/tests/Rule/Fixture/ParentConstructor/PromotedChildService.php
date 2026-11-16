<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ParentConstructor;

final class PromotedChildService extends ParentService
{
    public function __construct(
        protected \stdClass $first,
        protected \ArrayObject $second,
    ) {
        parent::__construct($first, $second);
    }
}
