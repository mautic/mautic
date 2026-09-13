<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class MultiValueArrayReturnInAnonymousClass
{
    public function make(): object
    {
        return new class {
            public function threeKeyedValues(): array
            {
                return [
                    'first'  => 1,
                    'second' => 2,
                    'third'  => 3,
                ];
            }
        };
    }
}
