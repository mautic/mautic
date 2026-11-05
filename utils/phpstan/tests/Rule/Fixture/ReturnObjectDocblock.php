<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class ReturnObjectDocblock
{
    /**
     * @return SomeReturnObject
     */
    public function returnsObject()
    {
        return $this->make();
    }

    /**
     * @return self
     */
    public function returnsSelf()
    {
        return $this;
    }

    /**
     * @return static
     */
    public function returnsStatic()
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function returnsThis()
    {
        return $this;
    }

    /**
     * @return SomeReturnObject|null
     */
    public function nullableReturn()
    {
        return random_int(0, 1) ? $this->make() : null;
    }

    /**
     * @return SomeReturnObject
     */
    public function alsoReturnsNull()
    {
        if (random_int(0, 1)) {
            return null;
        }

        return $this->make();
    }

    /**
     * @return SomeReturnObject|AnotherReturnObject
     */
    public function unionReturn()
    {
        return random_int(0, 1) ? new SomeReturnObject() : new AnotherReturnObject();
    }

    /**
     * @return SomeReturnObject|AnotherReturnObject|null
     */
    public function nullableUnionReturn()
    {
        if (random_int(0, 1)) {
            return null;
        }

        return random_int(0, 1) ? new SomeReturnObject() : new AnotherReturnObject();
    }

    public function alreadyNative(): SomeReturnObject
    {
        return $this->make();
    }

    /**
     * @return string
     */
    public function scalarReturn()
    {
        return 'x';
    }

    /**
     * @return \Countable&\Traversable
     */
    public function intersectionReturn()
    {
        return new \ArrayIterator([1, 2]);
    }

    /**
     * @return iterable
     */
    public function iterableReturn()
    {
        return [1, 2, 3];
    }

    /**
     * @return iterable
     */
    public function generatorReturn()
    {
        yield $this->make();
    }

    /**
     * @return \Generator
     */
    public function generatorClassReturn()
    {
        yield $this->make();
    }

    /**
     * @return \Generator<int, SomeReturnObject>
     */
    public function genericGeneratorReturn()
    {
        yield $this->make();
    }

    /**
     * @return iterable<int, string>
     */
    public function genericIterableReturn()
    {
        return ['x'];
    }

    /**
     * @return iterable
     */
    public function conditionalIterableReturn(bool $enabled)
    {
        if ($enabled) {
            return [1, 2, 3];
        }
    }

    private function make(): SomeReturnObject
    {
        return new SomeReturnObject();
    }
}
