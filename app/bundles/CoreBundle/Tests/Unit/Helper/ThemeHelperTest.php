<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Templating\TemplateNameParser;
use Mautic\CoreBundle\Templating\TemplateReference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Templating\DelegatingEngine;
use Symfony\Component\Translation\TranslatorInterface;

class ThemeHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PathsHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $pathsHelper;

    /**
     * @var TemplatingHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $templatingHelper;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var CoreParametersHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $coreParameterHelper;

    protected function setUp()
    {
        $this->pathsHelper         = $this->createMock(PathsHelper::class);
        $this->templatingHelper    = $this->createMock(TemplatingHelper::class);
        $this->translator          = $this->createMock(TranslatorInterface::class);
        $this->coreParameterHelper = $this->createMock(CoreParametersHelper::class);
        $this->coreParameterHelper->method('getParameter')
            ->with('theme_import_allowed_extensions')
            ->willReturn(['json', 'twig', 'css', 'js', 'htm', 'html', 'txt', 'jpg', 'jpeg', 'png', 'gif']);
    }

    public function testExceptionThrownWithMissingConfig()
    {
        $this->expectException(FileNotFoundException::class);

        $this->pathsHelper->method('getSystemPath')
            ->with('themes', true)
            ->willReturn(__DIR__.'/resource/themes');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.core.theme.missing.files', $this->anything(), 'validators')
            ->willReturnCallback(
                function ($key, array $parameters) {
                    $this->assertContains('config.json', $parameters['%files%']);
                }
            );

        $this->getThemeHelper()->install(__DIR__.'/resource/themes/missing-config.zip');
    }

    public function testExceptionThrownWithMissingMessage()
    {
        $this->expectException(FileNotFoundException::class);

        $this->pathsHelper->method('getSystemPath')
            ->with('themes', true)
            ->willReturn(__DIR__.'/resource/themes');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.core.theme.missing.files', $this->anything(), 'validators')
            ->willReturnCallback(
                function ($key, array $parameters) {
                    $this->assertContains('message.html.twig', $parameters['%files%']);
                }
            );

        $this->getThemeHelper()->install(__DIR__.'/resource/themes/missing-message.zip');
    }

    public function testExceptionThrownWithMissingFeature()
    {
        $this->expectException(FileNotFoundException::class);

        $this->pathsHelper->method('getSystemPath')
            ->with('themes', true)
            ->willReturn(__DIR__.'/resource/themes');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.core.theme.missing.files', $this->anything(), 'validators')
            ->willReturnCallback(
                function ($key, array $parameters) {
                    $this->assertContains('page.html.twig', $parameters['%files%']);
                }
            );

        $this->getThemeHelper()->install(__DIR__.'/resource/themes/missing-feature.zip');
    }

    public function testThemeIsInstalled()
    {
        $fs = new Filesystem();
        $fs->copy(__DIR__.'/resource/themes/good.zip', __DIR__.'/resource/themes/good-tmp.zip');

        $this->pathsHelper->method('getSystemPath')
            ->with('themes', true)
            ->willReturn(__DIR__.'/resource/themes');

        $this->getThemeHelper()->install(__DIR__.'/resource/themes/good-tmp.zip');

        $this->assertFileExists(__DIR__.'/resource/themes/good-tmp');

        $fs->remove(__DIR__.'/resource/themes/good-tmp');
    }

    public function testThemeFallbackToDefaultIfTemplateIsMissing()
    {
        $templateNameParser = $this->createMock(TemplateNameParser::class);
        $this->templatingHelper->expects($this->once())
            ->method('getTemplateNameParser')
            ->willReturn($templateNameParser);
        $templateNameParser->expects($this->once())
            ->method('parse')
            ->willReturn(
                new TemplateReference('', 'goldstar', 'page', 'html')
            );

        $templating = $this->createMock(DelegatingEngine::class);

        // twig does not exist
        $templating->expects($this->at(0))
            ->method('exists')
            ->willReturn(false);

        // php does not exist
        $templating->expects($this->at(1))
            ->method('exists')
            ->willReturn(false);

        // default themes twig exists
        $templating->expects($this->at(2))
            ->method('exists')
            ->willReturn(true);

        $this->templatingHelper->expects($this->once())
            ->method('getTemplating')
            ->willReturn($templating);

        $this->pathsHelper->method('getSystemPath')
            ->willReturnCallback(
                function ($path, $absolute) {
                    switch ($path) {
                        case 'themes':
                            return ($absolute) ? __DIR__.'/../../../../../../resource/themes' : 'themes';
                        case 'themes_root':
                            return __DIR__.'/../../../../../..';
                    }
                }
            );

        $themeHelper = $this->getThemeHelper();
        $themeHelper->setDefaultTheme('nature');

        $template = $themeHelper->checkForTwigTemplate(':goldstar:page.html.twig');
        $this->assertEquals(':nature:page.html.twig', $template);
    }

    public function testThemeFallbackToNextBestIfTemplateIsMissingForBothRequestedAndDefaultThemes()
    {
        $templateNameParser = $this->createMock(TemplateNameParser::class);
        $this->templatingHelper->expects($this->once())
            ->method('getTemplateNameParser')
            ->willReturn($templateNameParser);
        $templateNameParser->expects($this->once())
            ->method('parse')
            ->willReturn(
                new TemplateReference('', 'goldstar', 'page', 'html')
            );

        $templating = $this->createMock(DelegatingEngine::class);

        // twig does not exist
        $templating->expects($this->at(0))
            ->method('exists')
            ->willReturn(false);

        // php does not exist
        $templating->expects($this->at(1))
            ->method('exists')
            ->willReturn(false);

        // default theme twig does not exist
        $templating->expects($this->at(2))
            ->method('exists')
            ->willReturn(false);

        // next theme exists
        $templating->expects($this->at(3))
            ->method('exists')
            ->willReturn(true);

        $this->templatingHelper->expects($this->once())
            ->method('getTemplating')
            ->willReturn($templating);

        $this->pathsHelper->method('getSystemPath')
            ->willReturnCallback(
                function ($path, $absolute) {
                    switch ($path) {
                        case 'themes':
                            return ($absolute) ? __DIR__.'/../../../../../../themes' : 'themes';
                        case 'themes_root':
                            return __DIR__.'/../../../../../..';
                    }
                }
            );

        $themeHelper = $this->getThemeHelper();
        $themeHelper->setDefaultTheme('nature');

        $template = $themeHelper->checkForTwigTemplate(':goldstar:page.html.twig');
        $this->assertNotEquals(':nature:page.html.twig', $template);
        $this->assertNotEquals(':goldstar:page.html.twig', $template);
        $this->assertContains(':page.html.twig', $template);
    }

    /**
     * @return ThemeHelper
     */
    private function getThemeHelper()
    {
        return new ThemeHelper($this->pathsHelper, $this->templatingHelper, $this->translator, $this->coreParameterHelper);
    }
}
