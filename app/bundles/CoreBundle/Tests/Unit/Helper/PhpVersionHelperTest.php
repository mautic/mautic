<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\PhpVersionHelper;

class PhpVersionHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCurrentSemver()
    {
        $helper = new PhpVersionHelper();

        $this->assertSame(
            PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION,
            $helper->getCurrentSemver()
        );
    }
}
