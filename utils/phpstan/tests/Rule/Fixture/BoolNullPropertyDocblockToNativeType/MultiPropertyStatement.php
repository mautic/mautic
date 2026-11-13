<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullPropertyDocblockToNativeType;

final class MultiPropertyStatement
{
    /**
     * @var bool|null
     */
    private $first, $second;
}
