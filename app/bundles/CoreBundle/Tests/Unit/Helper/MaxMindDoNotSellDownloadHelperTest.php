<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\MaxMindDoNotSellDownloadHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class MaxMindDoNotSellDownloadHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @const TEMP_TEST_FILE
     */
    public const TEMP_TEST_FILE = './DoNotSellTest.json';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $loggerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|HttpClientInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $httpClientMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CoreParametersHelper
     */
    private \PHPUnit\Framework\MockObject\MockObject $coreParametersHelperMock;

    protected function setUp(): void
    {
        $this->loggerMock               = $this->createMock(LoggerInterface::class);
        $this->httpClientMock           = $this->createMock(HttpClientInterface::class);
        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('maxmind_do_not_sell_list_path')
            ->willReturn(self::TEMP_TEST_FILE);
    }

    protected function tearDown(): void
    {
        $filename = self::TEMP_TEST_FILE;

        if (is_file($filename)) {
            unlink($filename);
        }
    }

    /**
     * Test downloading data store without license.
     */
    public function testDownloadRemoteDataStoreWhenNoLicense(): void
    {
        $maxMindDoNotSellDownloadHelper = new MaxMindDoNotSellDownloadHelper('id', $this->loggerMock, $this->httpClientMock, $this->coreParametersHelperMock);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Missing user ID or license key for MaxMind');
        $result = $maxMindDoNotSellDownloadHelper->downloadRemoteDataStore();
        $this->assertFalse($result);
    }

    /**
     * Test downloading data store when transport exception.
     */
    public function testDownloadRemoteDataStoreWhenTransportException(): void
    {
        $maxMindDoNotSellDownloadHelper = new MaxMindDoNotSellDownloadHelper('id:license', $this->loggerMock, $this->httpClientMock, $this->coreParametersHelperMock);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Failed to fetch remote Do Not Sell data: transportException');
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.maxmind.com/privacy/exclusions', ['auth_basic' => ['id', 'license']])
            ->will($this->throwException(new TransportException('transportException')));
        $result = $maxMindDoNotSellDownloadHelper->downloadRemoteDataStore();
        $this->assertFalse($result);
    }

    /**
     * Test downloading data store when status code 500.
     */
    public function testDownloadRemoteDataStoreWhenStatusCode500(): void
    {
        $maxMindDoNotSellDownloadHelper = new MaxMindDoNotSellDownloadHelper('id:license', $this->loggerMock, $this->httpClientMock, $this->coreParametersHelperMock);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Wrong status code for Do Not Sell data: 500');
        $responseMock = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.maxmind.com/privacy/exclusions', ['auth_basic' => ['id', 'license']])
            ->willReturn($responseMock);
        $responseMock->expects($this->exactly(3))
            ->method('getStatusCode')
            ->willReturn(500);
        $result = $maxMindDoNotSellDownloadHelper->downloadRemoteDataStore();
        $this->assertFalse($result);
    }

    /**
     * Test downloading data store when getContent error.
     */
    public function testDownloadRemoteDataStoreWhenGetContentError(): void
    {
        $maxMindDoNotSellDownloadHelper = new MaxMindDoNotSellDownloadHelper('id:license', $this->loggerMock, $this->httpClientMock, $this->coreParametersHelperMock);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Failed to get content from remote Do Not Sell data: noContent');
        $responseMock = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.maxmind.com/privacy/exclusions', ['auth_basic' => ['id', 'license']])
            ->willReturn($responseMock);
        $responseMock->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);
        $responseMock->expects($this->once())
            ->method('getContent')
            ->will($this->throwException(new \Exception('noContent')));
        $result = $maxMindDoNotSellDownloadHelper->downloadRemoteDataStore();
        $this->assertFalse($result);
    }

    /**
     * Test downloading data store when OK.
     */
    public function testDownloadRemoteDataStoreWhenOK(): void
    {
        $maxMindDoNotSellDownloadHelper = new MaxMindDoNotSellDownloadHelper('id:license', $this->loggerMock, $this->httpClientMock, $this->coreParametersHelperMock);
        $responseMock                   = $this->createMock(ResponseInterface::class);
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.maxmind.com/privacy/exclusions', ['auth_basic' => ['id', 'license']])
            ->willReturn($responseMock);
        $responseMock->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);
        $responseMock->expects($this->once())
            ->method('getContent')
            ->willReturn('["mautic"]');
        $result = $maxMindDoNotSellDownloadHelper->downloadRemoteDataStore();
        $this->assertTrue($result);
        $this->assertFileExists($maxMindDoNotSellDownloadHelper->getLocalDataStoreFilepath());
    }
}
