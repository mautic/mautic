<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CoreParametersHelperTest extends TestCase
{
    /**
     * @var MockObject|ContainerInterface
     */
    private $container;

    /**
     * @var CoreParametersHelper
     */
    private $helper;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->helper    = new CoreParametersHelper($this->container);
    }

    public function testAllReturnsResolvedParameters()
    {
        $all = $this->helper->all();

        // Assert that a few of the config keys exist
        Assert::assertArrayHasKey('api_enabled', $all);
        Assert::assertArrayHasKey('cache_path', $all);
        Assert::assertArrayHasKey('log_path', $all);
    }
}
