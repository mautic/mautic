<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ParamFamily;

final class ChildOverridingObjectParam extends BaseWithObjectParam
{
    /**
     * @param SomeValueObject $item
     */
    public function handle($item): void
    {
        $item->doThing();
    }
}
