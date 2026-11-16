<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AliasBundle;

final class AliasHelperConsumer
{
    public function __construct(
        private UsedAliasHelper $usedAliasHelper,
    ) {
    }
}
