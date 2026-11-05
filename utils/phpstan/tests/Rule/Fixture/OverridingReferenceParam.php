<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class OverridingReferenceParam extends ParentWithReferenceParam
{
    // the "&" is only read here, but the parent signature mandates it
    public function handle(object &$item): void
    {
        $item->process();
    }
}
