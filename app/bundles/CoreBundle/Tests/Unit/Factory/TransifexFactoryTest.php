<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Factory;

use Mautic\CoreBundle\Factory\TransifexFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\Transifex\Connector\Resources;
use Mautic\Transifex\Exception\MissingCredentialsException;
use Mautic\Transifex\TransifexInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;

class TransifexFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ClientInterface&MockObject
     */
    private $client;

    /**
     * @var CoreParametersHelper&MockObject
     */
    private $coreParametersHelper;

    private TransifexFactory $transifexFactory;

    protected function setUp(): void
    {
        $this->client               = $this->createMock(ClientInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->transifexFactory     = new TransifexFactory($this->client, $this->coreParametersHelper);
    }

    public function testCreatingTransifexWithoutCredentials(): void
    {
        $this->expectException(MissingCredentialsException::class);
        $this->transifexFactory->getTransifex();
    }

    public function testCreatingTransifexWithCredentials(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('transifex_api_token')
            ->willReturn('the_api_key');

        $transifex = $this->transifexFactory->getTransifex();

        Assert::assertTrue($transifex instanceof TransifexInterface);

        // Getting a connector validates the config, so this should throw an exception.
        Assert::assertTrue($transifex->getConnector(Resources::class) instanceof Resources);
    }
}
