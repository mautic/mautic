<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Joomla\Http\Http;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

class LanguageHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PathsHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $pathsHelper;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var CoreParametersHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $coreParametersHelper;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connector;

    /**
     * @var string
     */
    private $translationsPath;

    /**
     * @var string
     */
    private $tmpPath;

    protected function setUp()
    {
        $this->pathsHelper          = $this->createMock(PathsHelper::class);
        $this->logger               = $this->createMock(Logger::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->connector            = $this->createMock(Http::class);

        $this->translationsPath = __DIR__.'/resource/language';
        $this->tmpPath          = $this->translationsPath.'/tmp';
    }

    public function testLanguageIsInstalled()
    {
        $this->pathsHelper->method('getSystemPath')
            ->withConsecutive(['translations_root'], ['cache'], ['cache'], ['tmp'])
            ->willReturnOnConsecutiveCalls($this->translationsPath, $this->tmpPath, $this->tmpPath, $this->tmpPath);

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
        $this->coreParametersHelper->method('getParameter')
            ->withConsecutive(['language_list_file'], ['translations_list_url'])
            ->willReturnOnConsecutiveCalls(
                '',
                'https://languages.test'
            );

        $this->pathsHelper->method('getSystemPath')
            ->withConsecutive(['translations_root'], ['cache'])
            ->willReturnOnConsecutiveCalls($this->translationsPath, $this->tmpPath);

        $languages      = ['languages' => [['name'=>'Spanish', 'locale'=>'es']]];
        $response       = new \stdClass();
        $response->code = 200;
        $response->body = json_encode($languages);

        $this->connector->expects($this->once())
            ->method('get')
            ->with('https://languages.test', [], 10)
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

        $this->coreParametersHelper->method('getParameter')
            ->with('translations_fetch_url')
            ->willReturn('https://languages.test/');

        $this->pathsHelper->method('getSystemPath')
            ->withConsecutive(['translations_root'], ['cache'], ['cache'])
            ->willReturnOnConsecutiveCalls($this->translationsPath, $this->tmpPath, $this->tmpPath);

        $response       = new \stdClass();
        $response->code = 200;
        $response->body = file_get_contents($this->translationsPath.'/es.zip');

        $this->connector->expects($this->once())
            ->method('get')
            ->with('https://languages.test/es.zip')
            ->willReturn($response);

        $error = $this->getHelper()->fetchPackage('es');
        @unlink($langFile);
        $this->assertFalse($error['error']);

        $this->assertFileExists($this->tmpPath.'/es.zip');
        @unlink($this->tmpPath.'/es.zip');
    }

    /**
     * @return LanguageHelper
     */
    private function getHelper()
    {
        return new LanguageHelper($this->pathsHelper, $this->logger, $this->coreParametersHelper, $this->connector);
    }
}
