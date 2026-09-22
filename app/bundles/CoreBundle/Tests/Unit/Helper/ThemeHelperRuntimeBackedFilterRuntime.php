<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

final class ThemeHelperRuntimeBackedFilterRuntime
{
    public function transform(string $value): string
    {
        return $value.' [runtime]';
    }
}
