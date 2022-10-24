<?php

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\Swiftmailer\Sparkpost\SparkpostFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SparkpostTransportTest extends TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|TransportCallback
     */
    private $transportCallback;

    /**
     * @var MockObject|SparkpostFactoryInterface
     */
    private $sparkpostFactory;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|CoreParametersHelper
     */
    private $coreParametersHelper;

    private SparkpostTransport $sparkPost;

    private string $apiKey;

    /**
     * @param mixed[]    $data
     * @param int|string $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->apiKey                    = 'test_key';
        $this->translator                = $this->createMock(TranslatorInterface::class);
        $this->transportCallback         = $this->createMock(TransportCallback::class);
        $this->sparkpostFactory          = $this->createMock(SparkpostFactoryInterface::class);
        $this->logger                    = $this->createMock(LoggerInterface::class);
        $this->coreParametersHelper      = $this->createMock(CoreParametersHelper::class);
    }

    /**
     * @return iterable<mixed[]>
     */
    public function dataGetHostProvider(): iterable
    {
        yield ['us', 'api.sparkpost.com'];
        yield ['eu', 'api.eu.sparkpost.com'];
    }

    /**
     * @dataProvider dataGetHostProvider
     */
    public function testGetHost(string $region, string $expected): void
    {
        $this->coreParametersHelper->method('get')
            ->with('mailer_sparkpost_region')
            ->willReturn($region);
        $this->sparkPost = new SparkpostTransport($this->apiKey, $this->translator, $this->transportCallback, $this->sparkpostFactory, $this->logger, $this->coreParametersHelper);

        self::assertSame($expected, $this->sparkPost->getHost());
    }
}
