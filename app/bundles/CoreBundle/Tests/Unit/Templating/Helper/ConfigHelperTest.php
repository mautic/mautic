<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Templating\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Templating\Helper\ConfigHelper;
use PHPUnit\Framework\Assert;

class ConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $coreParametersHelper = new class() extends CoreParametersHelper {
            public function __construct()
            {
            }

            public function get($name, $default = null)
            {
                Assert::assertEquals('param_a', $name);

                return 'value A';
            }
        };

        $helper = new ConfigHelper($coreParametersHelper);

        Assert::assertEquals('value A', $helper->get('param_a'));
    }

    public function testGetName()
    {
        $coreParametersHelper = new class() extends CoreParametersHelper {
            public function __construct()
            {
            }
        };

        $helper = new ConfigHelper($coreParametersHelper);

        Assert::assertEquals('config', $helper->getName());
    }
}
