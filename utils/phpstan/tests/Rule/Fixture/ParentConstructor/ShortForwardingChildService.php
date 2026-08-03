<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ParentConstructor;

final class ShortForwardingChildService extends ParentService
{
    public function __construct(
        \stdClass $first,
        \ArrayObject $second,
        \SplStack $third,
        private readonly \SplDoublyLinkedList $own,
    ) {
        parent::__construct($first, $second, $third, new \SplQueue(), new \SplFixedArray(), new \ArrayIterator(), new \SplObjectStorage(), new \DateTime(), new \DateInterval('P1D'), new \DateTimeZone('UTC'));
    }
}
