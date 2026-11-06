<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ParentConstructor;

final class ForwardingChildService extends ParentService
{
    public function __construct(
        \stdClass $first,
        \ArrayObject $second,
        \SplStack $third,
        \SplQueue $fourth,
        \SplFixedArray $fifth,
        \ArrayIterator $sixth,
        \SplObjectStorage $seventh,
        \DateTime $eighth,
        \DateInterval $ninth,
        \DateTimeZone $tenth,
        private readonly \SplDoublyLinkedList $own,
    ) {
        parent::__construct($first, $second, $third, $fourth, $fifth, $sixth, $seventh, $eighth, $ninth, $tenth);
    }
}
