<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullPropertyDocblockToNativeType;

trait SharedPropertyTrait
{
    /**
     * @var bool|null
     */
    private $traitFlag;
}
