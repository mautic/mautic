<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullReturnDocblockToNativeType;

final class StringGenericTraitUser
{
    /** @use GenericTrait<string> */
    use GenericTrait;
}
