<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class LanguageHelperTest extends TestCase
{
    /**
     * @var PathsHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $pathsHelper;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var CoreParametersHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $coreParametersHelper;

    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $client;

    /**
     * @var string
     */
    private $translationsPath;

    /**
     * @var string
     */
    private $tmpPath;

    protected function setUp(): void
    {
        $this->logger               = $this->createMock(Logger::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->client               = $this->createMock(Client::class);

        $this->translationsPath = __DIR__.'/resource/language';
        $this->tmpPath          = $this->translationsPath.'/tmp';

        $this->pathsHelper = $this->createMock(PathsHelper::class);
        $this->pathsHelper->method('getSystemPath')
            ->willReturnCallback(
                function ($path) {
                    switch ($path) {
                        case 'translations_root':
                            return $this->translationsPath;
                        case 'cache':
                        case 'tmp':
                            return $this->tmpPath;
                    }
                }
            );
    }

    public function testLanguageIsInstalled()
    {
        $filesystem = new Filesystem();

        // copy the zip to the tmp folder so the helper does not delete the test zip
        $filesystem->copy($this->translationsPath.'/es.zip', $this->tmpPath.'/es.zip');

        $helper = $this->getHelper();
        $error  = $helper->extractLanguagePackage('es');

        $this->assertFalse($error['error']);
        $this->assertFileExists($this->translationsPath.'/translations/es');

        // Cleanup the test
        $filesystem->remove($this->translationsPath.'/translations/es');
    }

    public function testLanguageListIsFetchedAndWritten()
    {
        $langFile = $this->tmpPath.'/../languageList.txt';
        $this->coreParametersHelper->method('get')
            ->withConsecutive(['language_list_file'], ['translations_list_url'])
            ->willReturnOnConsecutiveCalls(
                '',
                'https://languages.test'
            );

        $languages      = ['languages' => [['name'=>'Spanish', 'locale'=>'es']]];
        $response       = new Response(200, [], json_encode($languages));

        $this->client->expects($this->once())
            ->method('get')
            ->with('https://languages.test', [
                \GuzzleHttp\RequestOptions::TIMEOUT => 10,
            ])
            ->willReturn($response);

        $this->getHelper()->fetchLanguages();

        $this->assertFileExists($langFile);

        $written = json_decode(file_get_contents($langFile), true);
        $this->assertEquals($languages['languages'][0], $written['languages']['es']);

        @unlink($langFile);
    }

    public function testLanguageIsFetched()
    {
        $languages = ['languages' => ['es' => []]];
        $langFile  = $this->tmpPath.'/../languageList.txt';
        file_put_contents($langFile, json_encode($languages));

        $this->coreParametersHelper->method('get')
            ->with('translations_fetch_url')
            ->willReturn('https://languages.test/');

        $response = new Response(200, [], file_get_contents($this->translationsPath.'/es.zip'));

        $this->client->expects($this->once())
            ->method('get')
            ->with('https://languages.test/es.zip')
            ->willReturn($response);

        $error = $this->getHelper()->fetchPackage('es');
        @unlink($langFile);
        $this->assertFalse($error['error']);

        $this->assertFileExists($this->tmpPath.'/es.zip');
        @unlink($this->tmpPath.'/es.zip');
    }

    public function testSupportedLanguagesAreReturned()
    {
        $helper = $this->getHelper();
        $this->assertEquals(['en_US' => 'English - United States'], $helper->getSupportedLanguages());
    }

    /**
     * @return LanguageHelper
     */
    private function getHelper()
    {
        return new LanguageHelper($this->pathsHelper, $this->logger, $this->coreParametersHelper, $this->client);
    }
}
