<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

class SelfIntersectionReturn
{
    /**
     * @return self&\Countable
     */
    public function build(SelfIntersectionReturn&\Countable $input)
    {
        return $input;
    }
}
