<?php

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
