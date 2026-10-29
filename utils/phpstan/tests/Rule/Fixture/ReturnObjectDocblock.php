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

    private function make(): SomeReturnObject
    {
        return new SomeReturnObject();
    }
}
