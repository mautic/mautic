<?php

namespace Mautic\CoreBundle\Tests\unit\Helper;

use Mautic\CoreBundle\Helper\MaxMindDoNotSellDownloadHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class MaxMindDoNotSellDownloadHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @const TEMP
     */
    const TEMP = '.';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|HttpClientInterface
     */
    private $httpClientMock;

    protected function setUp()
    {
        $this->loggerMock     = $this->createMock(LoggerInterface::class);
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
    }

    protected function tearDown()
    {
        $dir      = './../'.MaxMindDoNotSellDownloadHelper::CACHE_DIR;
        $filename = $dir.'/'.MaxMindDoNotSellDownloadHelper::LOCAL_FILENAME;

        if (is_file($filename)) {
            unlink($filename);
        }

        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    /**
     * Test downloading data store without license.
     */
    public function testDownloadRemoteDataStoreWhenNoLicense()
    {
        $maxMindDoNotSellDownloadHelper = new MaxMindDoNotSellDownloadHelper('id', self::TEMP, $this->loggerMock, $this->httpClientMock);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Missing user ID or license key for MaxMind');
        $result = $maxMindDoNotSellDownloadHelper->downloadRemoteDataStore();
        $this->assertFalse($result);
    }

    /**
     * Test downloading data store when transport exception.
     */
    public function testDownloadRemoteDataStoreWhenTransportException()
    {
        $maxMindDoNotSellDownloadHelper = new MaxMindDoNotSellDownloadHelper('id:license', self::TEMP, $this->loggerMock, $this->httpClientMock);
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
    public function testDownloadRemoteDataStoreWhenStatusCode500()
    {
        $maxMindDoNotSellDownloadHelper = new MaxMindDoNotSellDownloadHelper('id:license', self::TEMP, $this->loggerMock, $this->httpClientMock);
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
    public function testDownloadRemoteDataStoreWhenGetContentError()
    {
        $maxMindDoNotSellDownloadHelper = new MaxMindDoNotSellDownloadHelper('id:license', self::TEMP, $this->loggerMock, $this->httpClientMock);
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
    public function testDownloadRemoteDataStoreWhenOK()
    {
        $maxMindDoNotSellDownloadHelper = new MaxMindDoNotSellDownloadHelper('id:license', self::TEMP, $this->loggerMock, $this->httpClientMock);
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
