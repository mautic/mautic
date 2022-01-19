<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
