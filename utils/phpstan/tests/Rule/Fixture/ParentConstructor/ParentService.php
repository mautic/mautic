<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ParentConstructor;

class ParentService
{
    public function __construct(
        protected \stdClass $first,
        protected \ArrayObject $second,
        protected \SplStack $third,
        protected \SplQueue $fourth,
        protected \SplFixedArray $fifth,
        protected \ArrayIterator $sixth,
        protected \SplObjectStorage $seventh,
        protected \DateTime $eighth,
        protected \DateInterval $ninth,
        protected \DateTimeZone $tenth,
    ) {
    }
}
