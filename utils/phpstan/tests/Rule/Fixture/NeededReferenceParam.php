<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class NeededReferenceParam
{
    // final class: methods reach the write/escape analysis instead of the overridable short-circuit

    public function reassign(int &$value): void
    {
        $value = 1;
    }

    public function appendToArray(array &$items): void
    {
        $items[] = 5;
    }

    public function increment(int &$count): void
    {
        ++$count;
    }

    public function passedToByRefFunction(array &$matches): void
    {
        preg_match('/\d+/', 'a1', $matches);
    }

    public function sortInPlace(array &$list): void
    {
        sort($list);
    }

    public function destructure(int &$first): void
    {
        [$first, $second] = [1, 2];
    }

    public function capturedByReference(int &$accumulator): void
    {
        $add = function () use (&$accumulator): void {
            ++$accumulator;
        };
        $add();
    }

    public function passedToMethod(object &$dependency): void
    {
        $this->consume($dependency);
    }

    public function passedToConstructor(array &$payload): void
    {
        new \ArrayObject($payload);
    }

    /**
     * @return array<int, int>
     */
    public function referencedInArrayLiteral(int &$captured): array
    {
        return [&$captured];
    }

    private function consume(object &$object): void
    {
        $object = new \stdClass();
    }
}
