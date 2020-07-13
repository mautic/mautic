<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\Update\Exception\LatestVersionSupportedException;
use Mautic\CoreBundle\Helper\Update\Github\ReleaseParser;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class UpdateHelperTest extends TestCase
{
    /**
     * @var PathsHelper|MockObject
     */
    private $pathsHelper;

    /**
     * @var Logger|MockObject
     */
    private $logger;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var Client|MockObject
     */
    private $client;

    /**
     * @var ResponseInterface|MockObject
     */
    private $response;

    /**
     * @var StreamInterface|MockObject
     */
    private $streamBody;

    /**
     * @var ReleaseParser|MockObject
     */
    private $releaseParser;

    /**
     * @var UpdateHelper
     */
    private $helper;

    protected function setUp()
    {
        $this->pathsHelper = $this->createMock(PathsHelper::class);
        $this->pathsHelper->method('getSystemPath')
            ->with('cache')
            ->willReturn(__DIR__.'/resource/update/tmp');

        $this->logger               = $this->createMock(Logger::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->releaseParser        = $this->createMock(ReleaseParser::class);

        $this->response   = $this->createMock(ResponseInterface::class);
        $this->streamBody = $this->createMock(StreamInterface::class);
        $this->response
            ->method('getBody')
            ->willReturn($this->streamBody);
        $this->client = $this->createMock(Client::class);

        $this->helper = new UpdateHelper($this->pathsHelper, $this->logger, $this->coreParametersHelper, $this->client, $this->releaseParser);
    }

    protected function tearDown()
    {
        parent::tearDown();

        // Cleanup the files
        @unlink(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt');
    }

    public function testUpdatePackageFetchedAndSaved()
    {
        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->streamBody->expects($this->once())
            ->method('getContents')
            ->willReturn(file_get_contents(__DIR__.'/resource/update/update.zip'));

        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', 'update.zip')
            ->willReturn($this->response);

        $result = $this->helper->fetchPackage('update.zip');
        $this->assertTrue(isset($result['error']));
        $this->assertFalse($result['error']);

        $updatePackage = __DIR__.'/resource/update/tmp/update.zip';
        $this->assertTrue(file_exists($updatePackage));
        @unlink($updatePackage);
    }

    public function testConnectionErrorReturnsError()
    {
        $this->response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(404);
        $this->response->expects($this->never())
            ->method('getBody');

        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', 'update.zip')
            ->willReturn($this->response);

        $result = $this->helper->fetchPackage('update.zip');
        $this->assertTrue(isset($result['error']));
        $this->assertTrue($result['error']);
        $this->assertEquals('mautic.core.updater.error.fetching.package', $result['message']);
    }

    public function testCacheIsRefreshedIfStabilityMismatches()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 100,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'alpha',
                null,
                null
            );

        $this->helper->fetchData();
    }

    public function testCacheIsRefreshedIfExpired()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                null,
                null
            );

        $this->helper->fetchData();
    }

    public function testCacheIsRefreshedIfForced()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time(),
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                null,
                null
            );

        $this->helper->fetchData(true);
    }

    public function testStatsAreSent()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $statsUrl = 'https://mautic.org/stats';
        $this->coreParametersHelper->expects($this->exactly(6))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['secret_key'],
                ['db_driver'],
                ['install_source', 'Mautic'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                $statsUrl,
                'abc123',
                'mysql',
                'Mautic',
                null
            );

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $statsUrl,
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey(\GuzzleHttp\RequestOptions::FORM_PARAMS, $options);
                        $this->assertArrayHasKey(\GuzzleHttp\RequestOptions::CONNECT_TIMEOUT, $options);
                        $this->assertArrayHasKey(\GuzzleHttp\RequestOptions::HEADERS, $options);
                        // We need to send an Accept header to the stats server or we'll get 500 errors
                        $this->assertEquals(['Accept' => '*/*'], $options[\GuzzleHttp\RequestOptions::HEADERS]);

                        return true;
                    }
                )
            )->willReturn($this->response);

        $this->helper->fetchData();
    }

    public function testStatsNotSentIfDisabled()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $statsUrl = '';
        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                $statsUrl,
                null
            );

        $this->client->expects($this->never())
            ->method('request');

        $this->helper->fetchData();
    }

    public function testExceptionDoesNotGoUncaughtWhenThrownDuringUpdatingStats()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $statsUrl = 'https://mautic.org/stats';
        $this->coreParametersHelper->expects($this->exactly(6))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['secret_key'],
                ['db_driver'],
                ['install_source', 'Mautic'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                $statsUrl,
                'abc123',
                'mysql',
                'Mautic',
                null
            );

        $this->client->expects($this->once())
            ->method('request')
            ->with('POST', $statsUrl, $this->anything())
            ->willReturnCallback(
                function (string $method, string $url, array $options) {
                    $request = $this->createMock(RequestInterface::class);

                    throw new \Exception('something bad happened');
                }
            );

        $this->logger->expects($this->once())
            ->method('error');

        $this->helper->fetchData();
    }

    public function testRequestExceptionDoesNotGoUncaughtWhenThrownDuringUpdatingStats()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $statsUrl = 'https://mautic.org/stats';
        $this->coreParametersHelper->expects($this->exactly(6))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['secret_key'],
                ['db_driver'],
                ['install_source', 'Mautic'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                $statsUrl,
                'abc123',
                'mysql',
                'Mautic',
                null
            );

        $this->client->expects($this->once())
            ->method('request')
            ->with('POST', $statsUrl, $this->anything())
            ->willReturnCallback(
                function (string $method, string $url, array $options) {
                    $request = $this->createMock(RequestInterface::class);

                    throw new RequestException('something bad happened', $request, $this->response);
                }
            );

        $this->logger->expects($this->once())
            ->method('error');

        $this->helper->fetchData();
    }

    public function testRequestExceptionWithEmptyResponseDoesNotGoUncaughtWhenThrownDuringUpdatingStats()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $statsUrl = 'https://mautic.org/stats';
        $this->coreParametersHelper->expects($this->exactly(6))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['secret_key'],
                ['db_driver'],
                ['install_source', 'Mautic'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                $statsUrl,
                'abc123',
                'mysql',
                'Mautic',
                null
            );

        $this->client->expects($this->once())
            ->method('request')
            ->with('POST', $statsUrl, $this->anything())
            ->willReturnCallback(
                function (string $method, string $url, array $options) {
                    $request = $this->createMock(RequestInterface::class);

                    throw new RequestException('something bad happened', $request, null);
                }
            );

        $this->logger->expects($this->once())
            ->method('error');

        $this->helper->fetchData();
    }

    public function testNoErrorIfLatestVersionInstalled()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $updateUrl = 'https://mautic.org/update';
        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                null,
                $updateUrl
            );

        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', $updateUrl)
            ->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->streamBody->expects($this->once())
            ->method('getContents')
            ->willReturn('[{"html_url": "https://github.com/10.0.1"}]');

        $this->releaseParser->expects($this->once())
            ->method('getLatestSupportedRelease')
            ->willThrowException(new LatestVersionSupportedException());

        $data = $this->helper->fetchData();
        $this->assertFalse($data['error']);
        $this->assertEquals('mautic.core.updater.running.latest.version', $data['message']);
    }

    public function testErrorIfLatestVersionCouldNotBeDetermined()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $updateUrl = 'https://mautic.org/update';
        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                null,
                $updateUrl
            );

        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', $updateUrl)
            ->willReturn($this->response);

        $this->response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(404);

        $this->releaseParser->expects($this->never())
            ->method('getLatestSupportedRelease');

        $this->logger->expects($this->once())
            ->method('error');

        $data = $this->helper->fetchData();
        $this->assertTrue($data['error']);
        $this->assertEquals('mautic.core.updater.error.fetching.updates', $data['message']);
    }

    public function testErrorIfGuzzleException()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $updateUrl = 'https://mautic.org/update';
        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                null,
                $updateUrl
            );

        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', $updateUrl)
            ->willThrowException(new RequestException('bad', $this->createMock(RequestInterface::class), $this->response));

        $this->releaseParser->expects($this->never())
            ->method('getLatestSupportedRelease');

        $this->logger->expects($this->once())
            ->method('error');

        $data = $this->helper->fetchData();
        $this->assertTrue($data['error']);
        $this->assertEquals('mautic.core.updater.error.fetching.updates', $data['message']);
    }

    public function testErrorForAnyException()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $updateUrl = 'https://mautic.org/update';
        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                null,
                $updateUrl
            );

        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', $updateUrl)
            ->willThrowException(new \Exception());

        $this->releaseParser->expects($this->never())
            ->method('getLatestSupportedRelease');

        $this->logger->expects($this->once())
            ->method('error');

        $this->response->expects($this->never())
            ->method('getStatusCode');

        $data = $this->helper->fetchData();
        $this->assertTrue($data['error']);
        $this->assertEquals('mautic.core.updater.error.fetching.updates', $data['message']);
    }

    public function testNoErrorIfInAppUpdatesAreDisabled()
    {
        $cache = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => '10.0.1',
            'announcement' => 'https://mautic.org',
            'package'      => 'https://mautic.org/10.0.1/upgrade.zip',
            'stability'    => 'stable',
            'checkedTime'  => time() - 10800,
        ];
        file_put_contents(__DIR__.'/resource/update/tmp/lastUpdateCheck.txt', json_encode($cache));

        $this->coreParametersHelper->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['update_stability'],
                ['stats_update_url'],
                ['system_update_url']
            )
            ->willReturnOnConsecutiveCalls(
                'stable',
                null,
                null
            );

        $this->client->expects($this->never())
            ->method('request');

        $this->releaseParser->expects($this->never())
            ->method('getLatestSupportedRelease');

        $data = $this->helper->fetchData();
        $this->assertFalse($data['error']);
        $this->assertEquals('mautic.core.updater.running.latest.version', $data['message']);
    }
}
