<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Templating\TemplateNameParser;
use Mautic\CoreBundle\Templating\TemplateReference;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\BuilderInterface;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Templating\DelegatingEngine;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class ThemeHelperTest extends TestCase
{
    /**
     * @var PathsHelper|MockObject
     */
    private $pathsHelper;

    /**
     * @var TemplatingHelper|MockObject
     */
    private $templatingHelper;

    /**
     * @var TranslatorInterface|MockObject
     */
    private $translator;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParameterHelper;

    /**
     * @var BuilderIntegrationsHelper|MockObject
     */
    private $builderIntegrationsHelper;

    /**
     * @var ThemeHelper
     */
    private $themeHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pathsHelper         = $this->createMock(PathsHelper::class);
        $this->templatingHelper    = $this->createMock(TemplatingHelper::class);
        $this->translator          = $this->createMock(TranslatorInterface::class);
        $this->coreParameterHelper = $this->createMock(CoreParametersHelper::class);
        $this->coreParameterHelper->method('get')
            ->with('theme_import_allowed_extensions')
            ->willReturn(['json', 'twig', 'css', 'js', 'htm', 'html', 'txt', 'jpg', 'jpeg', 'png', 'gif']);

        $this->builderIntegrationsHelper = $this->createMock(BuilderIntegrationsHelper::class);

        $this->themeHelper = new ThemeHelper(
            $this->pathsHelper,
            $this->templatingHelper,
            $this->translator,
            $this->coreParameterHelper,
            new Filesystem(),
            new Finder(),
            $this->builderIntegrationsHelper
        );
    }

    public function testExceptionThrownWithMissingConfig(): void
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
                    $this->assertStringContainsString('config.json', $parameters['%files%']);
                }
            );

        $this->themeHelper->install(__DIR__.'/resource/themes/missing-config.zip');
    }

    public function testExceptionThrownWithMissingMessage(): void
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
                    $this->assertStringContainsString('message.html.twig', $parameters['%files%']);
                }
            );

        $this->themeHelper->install(__DIR__.'/resource/themes/missing-message.zip');
    }

    public function testExceptionThrownWithMissingFeature(): void
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
                    $this->assertStringContainsString('page.html.twig', $parameters['%files%']);
                }
            );

        $this->themeHelper->install(__DIR__.'/resource/themes/missing-feature.zip');
    }

    public function testThemeIsInstalled(): void
    {
        $fs = new Filesystem();
        $fs->copy(__DIR__.'/resource/themes/good.zip', __DIR__.'/resource/themes/good-tmp.zip');

        $this->pathsHelper->method('getSystemPath')
            ->with('themes', true)
            ->willReturn(__DIR__.'/resource/themes');

        $this->themeHelper->install(__DIR__.'/resource/themes/good-tmp.zip');

        $this->assertFileExists(__DIR__.'/resource/themes/good-tmp');

        $fs->remove(__DIR__.'/resource/themes/good-tmp');
    }

    public function testThemeFallbackToDefaultIfTemplateIsMissing(): void
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

        $templating->expects($this->exactly(3))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(
                false, // twig does not exist
                false, // php does not exist
                true // default themes twig exists
            );

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

        $this->themeHelper->setDefaultTheme('nature');

        $template = $this->themeHelper->checkForTwigTemplate(':goldstar:page.html.twig');
        $this->assertEquals(':nature:page.html.twig', $template);
    }

    public function testThemeFallbackToNextBestIfTemplateIsMissingForBothRequestedAndDefaultThemes(): void
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

        $templating->expects($this->exactly(4))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(
                // twig does not exist
                false,
                // php does not exist
                false,
                // default theme twig does not exist
                false,
                // next theme exists
                true
            );

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

        $this->themeHelper->setDefaultTheme('nature');

        $template = $this->themeHelper->checkForTwigTemplate(':goldstar:page.html.twig');
        $this->assertNotEquals(':nature:page.html.twig', $template);
        $this->assertNotEquals(':goldstar:page.html.twig', $template);
        $this->assertStringContainsString(':page.html.twig', $template);
    }

    public function testCopyWithNoNewDirName(): void
    {
        $themeHelper = new ThemeHelper(
            new class() extends PathsHelper {
                public function __construct()
                {
                }

                public function getSystemPath($name, $fullPath = false)
                {
                    Assert::assertSame('themes', $name);

                    return '/path/to/themes';
                }
            },
            new class() extends TemplatingHelper {
                public function __construct()
                {
                }
            },
            new class() extends Translator {
                public function __construct()
                {
                }
            },
            new class() extends CoreParametersHelper {
                public function __construct()
                {
                }
            },
            new class() extends Filesystem {
                public function __construct()
                {
                }

                public function exists($files)
                {
                    if ('/path/to/themes/new-theme-name' === $files) {
                        return false;
                    }

                    return true;
                }

                public function mirror($originDir, $targetDir, ?\Traversable $iterator = null, $options = [])
                {
                    Assert::assertSame('/path/to/themes/origin-template-dir', $originDir);
                    Assert::assertSame('/path/to/themes/new-theme-name', $targetDir);
                }

                public function readFile(string $filename): string
                {
                    Assert::assertStringEndsWith('/config.json', $filename);

                    return '{"name":"Origin Theme"}';
                }

                public function dumpFile($filename, $content)
                {
                    Assert::assertSame('/path/to/themes/new-theme-name/config.json', $filename);
                    Assert::assertSame('{"name":"New Theme Name"}', $content);
                }
            },
            new class() extends Finder {
                private $dirs = [];

                public function __construct()
                {
                }

                public function in($dirs)
                {
                    $this->dirs = [
                        new \SplFileInfo('origin-template-dir'),
                    ];

                    return $this;
                }

                public function getIterator()
                {
                    return new \ArrayIterator($this->dirs);
                }
            },
            $this->builderIntegrationsHelper
        );

        $themeHelper->copy('origin-template-dir', 'New Theme Name');
    }

    public function testCopyWithNewDirName(): void
    {
        $themeHelper = new ThemeHelper(
            new class() extends PathsHelper {
                public function __construct()
                {
                }

                public function getSystemPath($name, $fullPath = false)
                {
                    Assert::assertSame('themes', $name);

                    return '/path/to/themes';
                }
            },
            new class() extends TemplatingHelper {
                public function __construct()
                {
                }
            },
            new class() extends Translator {
                public function __construct()
                {
                }
            },
            new class() extends CoreParametersHelper {
                public function __construct()
                {
                }
            },
            new class() extends Filesystem {
                public function __construct()
                {
                }

                public function exists($files)
                {
                    if ('/path/to/themes/requested-theme-dir' === $files) {
                        return false;
                    }

                    return true;
                }

                public function mirror($originDir, $targetDir, ?\Traversable $iterator = null, $options = [])
                {
                    Assert::assertSame('/path/to/themes/origin-template-dir', $originDir);
                    Assert::assertSame('/path/to/themes/requested-theme-dir', $targetDir);
                }

                public function readFile(string $filename): string
                {
                    Assert::assertStringEndsWith('/config.json', $filename);

                    return '{"name":"Origin Theme"}';
                }

                public function dumpFile($filename, $content)
                {
                    Assert::assertSame('/path/to/themes/requested-theme-dir/config.json', $filename);
                    Assert::assertSame('{"name":"New Theme Name"}', $content);
                }
            },
            new class() extends Finder {
                private $dirs = [];

                public function __construct()
                {
                }

                public function in($dirs)
                {
                    $this->dirs = [
                        new \SplFileInfo('origin-template-dir'),
                    ];

                    return $this;
                }

                public function getIterator()
                {
                    return new \ArrayIterator($this->dirs);
                }
            },
            $this->builderIntegrationsHelper
        );

        $themeHelper->copy('origin-template-dir', 'New Theme Name', 'requested-theme-dir');
    }

    public function testLegacyThemesAreReturnedForFeatureIfNoCustomBuilderIsEnabled(): void
    {
        $this->builderIntegrationsHelper->method('getBuilder')
            ->willThrowException(new IntegrationNotFoundException());

        $this->pathsHelper->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes('email');
        Assert::assertCount(2, $themes);
        Assert::assertArrayHasKey('theme-legacy-email', $themes);
        Assert::assertArrayHasKey('theme-legacy-all', $themes);

        $themes = $this->themeHelper->getInstalledThemes('page');
        Assert::assertCount(1, $themes);
        Assert::assertArrayHasKey('theme-legacy-all', $themes);
    }

    public function testCustomThemesAreReturnedForFeatureIfCustomBuilderIsEnabled(): void
    {
        $mockBuilder = $this->createMock(BuilderInterface::class);
        $mockBuilder->method('getName')
            ->willReturn('custom');

        $integration = new Integration();
        $integration->setIsPublished(true);

        $mockBuilder->method('getIntegrationConfiguration')
            ->willReturn($integration);
        $this->builderIntegrationsHelper->method('getBuilder')
            ->willReturn($mockBuilder);

        $this->pathsHelper->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes('page');
        Assert::assertCount(2, $themes);
        Assert::assertArrayHasKey('theme-custom-builder-all', $themes);
        Assert::assertArrayHasKey('theme-custom-builder-page', $themes);

        $themes = $this->themeHelper->getInstalledThemes('email');
        Assert::assertCount(1, $themes);
        Assert::assertArrayHasKey('theme-custom-builder-all', $themes);
    }

    public function testAllThemesAreReturned(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes();
        Assert::assertCount(4, $themes);

        // Test that a list of themes are returned by default
        $themeKeys   = array_keys($themes);
        $themeValues = array_values($themes);
        Assert::assertSame($themeKeys, $themeValues);
    }

    public function testExtendedThemeDetailsAreReturned(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes('all', true);
        Assert::assertCount(4, $themes);
        Assert::assertArrayHasKey('name', $themes['theme-legacy-email']);
        Assert::assertArrayHasKey('dir', $themes['theme-legacy-email']);
    }

    public function testExtendedThemeDetailsWithoutDirectoriesAreReturned(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes('all', true, false, false);
        Assert::assertCount(4, $themes);
        Assert::assertArrayHasKey('name', $themes['theme-legacy-email']);
        Assert::assertArrayNotHasKey('dir', $themes['theme-legacy-email']);
    }

    public function testCachedThemesReturnAsExpected(): void
    {
        $this->builderIntegrationsHelper->method('getBuilder')
            ->willThrowException(new IntegrationNotFoundException());

        $this->pathsHelper
            ->expects($this->exactly(2))
            ->method('getSystemPath')
            ->withConsecutive(
                ['themes', true],
                ['themes', false]
            )
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes('all', true, false, false);
        Assert::assertCount(4, $themes);
        Assert::assertArrayHasKey('name', $themes['theme-legacy-email']);
        Assert::assertArrayNotHasKey('dir', $themes['theme-legacy-email']);

        // this should return cached results
        $themes = $this->themeHelper->getInstalledThemes('all', true, false, false);
        Assert::assertCount(4, $themes);
        Assert::assertArrayHasKey('name', $themes['theme-legacy-email']);
        Assert::assertArrayNotHasKey('dir', $themes['theme-legacy-email']);

        $themes = $this->themeHelper->getInstalledThemes('page', true, false, false);
        Assert::assertCount(1, $themes);
        Assert::assertArrayHasKey('name', $themes['theme-legacy-all']);
        Assert::assertArrayNotHasKey('dir', $themes['theme-legacy-all']);

        $themes = $this->themeHelper->getInstalledThemes('page', true, false, true);
        Assert::assertCount(1, $themes);
        Assert::assertArrayHasKey('name', $themes['theme-legacy-all']);
        Assert::assertArrayHasKey('dir', $themes['theme-legacy-all']);
    }
}
