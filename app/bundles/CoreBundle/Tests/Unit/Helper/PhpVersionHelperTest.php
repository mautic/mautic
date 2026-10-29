<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Mautic\CoreBundle\Helper\PhpVersionHelper;

final class PhpVersionHelperTest extends TestCase
{
    public function testGetCurrentSemver(): void
    {
        $helper = new PhpVersionHelper();

        $this->assertSame(
            PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION,
            $helper->getCurrentSemver()
        );
    }
}
