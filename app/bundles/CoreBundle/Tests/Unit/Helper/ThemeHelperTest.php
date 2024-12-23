<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\BuilderInterface;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ThemeHelperTest extends TestCase
{
    /**
     * @var PathsHelper|MockObject
     */
    private MockObject $pathsHelper;

    /**
     * @var Environment|MockObject
     */
    private MockObject $twig;

    /**
     * @var FilesystemLoader|MockObject
     */
    private MockObject $loader;

    /**
     * @var TranslatorInterface|MockObject
     */
    private MockObject $translator;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private MockObject $coreParameterHelper;

    /**
     * @var BuilderIntegrationsHelper|MockObject
     */
    private MockObject $builderIntegrationsHelper;

    private ThemeHelper $themeHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pathsHelper         = $this->createMock(PathsHelper::class);
        $this->twig                = $this->createMock(Environment::class);
        $this->loader              = $this->createMock(FilesystemLoader::class);
        $this->translator          = $this->createMock(TranslatorInterface::class);
        $this->coreParameterHelper = $this->createMock(CoreParametersHelper::class);
        $this->coreParameterHelper->method('get')
            ->with('theme_import_allowed_extensions')
            ->willReturn(['json', 'twig', 'css', 'js', 'htm', 'html', 'txt', 'jpg', 'jpeg', 'png', 'gif']);

        $this->builderIntegrationsHelper = $this->createMock(BuilderIntegrationsHelper::class);

        $this->translator->method('trans')->willReturn('some translation');

        $this->themeHelper = new ThemeHelper(
            $this->pathsHelper,
            $this->twig,
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
                function ($key, array $parameters): void {
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
                function ($key, array $parameters): void {
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
                function ($key, array $parameters): void {
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
        $this->twig->expects($this->exactly(2))
            ->method('getLoader')
            ->willReturn($this->loader);

        $this->loader->expects($this->exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(
                false, // twig does not exist
                true, // default themes twig exists
            );

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

        $template = $this->themeHelper->checkForTwigTemplate('@themes/goldstar/html/page.html.twig');
        $this->assertEquals('@themes/_1-2-1-2-column/html/page.html.twig', $template);
    }

    public function testThemeFallbackToNextBestIfTemplateIsMissingForBothRequestedAndDefaultThemes(): void
    {
        $this->twig->expects($this->exactly(3))
            ->method('getLoader')
            ->willReturn($this->loader);

        $this->loader->expects($this->exactly(3))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(
                // twig does not exist
                false,
                // default theme twig does not exist
                false,
                // next theme exists
                true
            );

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

        $template = $this->themeHelper->checkForTwigTemplate('@themes/goldstar/page.html.twig');
        $this->assertNotEquals('@themes/nature/page.html.twig', $template);
        $this->assertNotEquals('@themes/goldstar/page.html.twig', $template);
        $this->assertStringContainsString('/page.html.twig', $template);
    }

    public function testCopyWithNoNewDirName(): void
    {
        $themeHelper = new ThemeHelper(
            new class extends PathsHelper {
                public function __construct()
                {
                }

                public function getSystemPath($name, $fullPath = false)
                {
                    Assert::assertSame('themes', $name);

                    return '/path/to/themes';
                }
            },
            new Environment(new FilesystemLoader()),
            new class extends Translator {
                public function __construct()
                {
                }
            },
            new class extends CoreParametersHelper {
                public function __construct()
                {
                }
            },
            new class extends Filesystem {
                public function __construct()
                {
                }

                /**
                 * @param string $files
                 */
                public function exists($files): bool
                {
                    if ('/path/to/themes/new-theme-name' === $files) {
                        return false;
                    }

                    return true;
                }

                /**
                 * @param ?\Traversable<mixed> $iterator
                 * @param mixed[]              $options
                 */
                public function mirror(string $originDir, string $targetDir, \Traversable $iterator = null, array $options = []): void
                {
                    Assert::assertSame('/path/to/themes/origin-template-dir', $originDir);
                    Assert::assertSame('/path/to/themes/new-theme-name', $targetDir);
                }

                public function readFile(string $filename): string
                {
                    Assert::assertStringEndsWith('/config.json', $filename);

                    return '{"name":"Origin Theme"}';
                }

                public function dumpFile(string $filename, $content): void
                {
                    Assert::assertSame('/path/to/themes/new-theme-name/config.json', $filename);
                    Assert::assertSame('{"name":"New Theme Name"}', $content);
                }
            },
            new class extends Finder {
                /** @var SplFileInfo[] */
                private array $dirs = [];

                public function __construct()
                {
                }

                public function in($dirs): static
                {
                    $this->dirs = [
                        new SplFileInfo('origin-template-dir', 'origin-template-dir', 'origin-template-dir'),
                    ];

                    return $this;
                }

                public function getIterator(): \Iterator
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
            new class extends PathsHelper {
                public function __construct()
                {
                }

                public function getSystemPath($name, $fullPath = false)
                {
                    Assert::assertSame('themes', $name);

                    return '/path/to/themes';
                }
            },
            new Environment(new FilesystemLoader()),
            new class extends Translator {
                public function __construct()
                {
                }
            },
            new class extends CoreParametersHelper {
                public function __construct()
                {
                }
            },
            new class extends Filesystem {
                public function __construct()
                {
                }

                /**
                 * @param string $files
                 */
                public function exists($files): bool
                {
                    if ('/path/to/themes/requested-theme-dir' === $files) {
                        return false;
                    }

                    return true;
                }

                /**
                 * @param ?\Traversable<mixed> $iterator
                 * @param array<mixed>         $options
                 */
                public function mirror(string $originDir, string $targetDir, \Traversable $iterator = null, array $options = []): void
                {
                    Assert::assertSame('/path/to/themes/origin-template-dir', $originDir);
                    Assert::assertSame('/path/to/themes/requested-theme-dir', $targetDir);
                }

                public function readFile(string $filename): string
                {
                    Assert::assertStringEndsWith('/config.json', $filename);

                    return '{"name":"Origin Theme"}';
                }

                public function dumpFile(string $filename, $content): void
                {
                    Assert::assertSame('/path/to/themes/requested-theme-dir/config.json', $filename);
                    Assert::assertSame('{"name":"New Theme Name"}', $content);
                }
            },
            new class extends Finder {
                /**
                 * @var SplFileInfo[]
                 */
                private array $dirs = [];

                public function __construct()
                {
                }

                public function in($dirs): static
                {
                    $this->dirs = [
                        new SplFileInfo('origin-template-dir', 'origin-template-dir', 'origin-template-dir'),
                    ];

                    return $this;
                }

                public function getIterator(): \Iterator
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

    public function testGetCurrentThemeWillReturnCodeModeIfTheThemeIsCodeMode(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        Assert::assertTrue($this->themeHelper->exists('theme-legacy-email'));
    }

    public function testExistsReturnsFalseIfThemeDoesNotExist(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        Assert::assertFalse($this->themeHelper->exists('theme-legacy-email-foo'));
    }

    public function testDefaultThemeNotShouldNotGetRemoved(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->exactly(5))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(true, true, true, true, true);

        $filesystem->method('readFile')->willReturn('{"name": "Test Theme"}');

        $themeHelper = new ThemeHelper(
            $this->pathsHelper,
            $this->twig,
            $this->translator,
            $this->coreParameterHelper,
            $filesystem,
            new Finder(),
            $this->builderIntegrationsHelper
        );

        // custom theme name - theme-legacy-email
        $themeHelper->delete('theme-legacy-email');
        Assert::assertTrue($themeHelper->exists('theme-legacy-email'));
    }

    public function testDeleteThemeThrowsExceptionIfThemeDoesNotExist(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $this->expectException(FileNotFoundException::class);
        $this->themeHelper->delete('theme-legacy-email-foo');
    }
}
