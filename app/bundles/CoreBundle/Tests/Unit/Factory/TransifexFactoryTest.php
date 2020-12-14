<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Factory;

use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Factory\TransifexFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;

class TransifexFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var TransifexFactory
     */
    private $transifexFactory;

    protected function setUp(): void
    {
        $this->client               = $this->createMock(ClientInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->transifexFactory     = new TransifexFactory($this->client, $this->coreParametersHelper);
    }

    public function testCreatingTransifexWithoutCredentials()
    {
        $this->expectException(BadConfigurationException::class);
        $this->transifexFactory->getTransifex();
    }

    public function testCreatingTransifexWithCredentials()
    {
        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['transifex_username'],
                ['transifex_password']
            )
            ->willReturnOnConsecutiveCalls(
                'the_username',
                'the_password'
            );

        $transifex = $this->transifexFactory->getTransifex();

        $this->assertSame('the_username', $transifex->getOption('api.username'));
        $this->assertSame('the_password', $transifex->getOption('api.password'));
    }
}
