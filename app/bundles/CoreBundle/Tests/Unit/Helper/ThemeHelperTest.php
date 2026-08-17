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
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFilter;

final class ThemeHelperTest extends TestCase
{
    /**
     * @var MockObject&PathsHelper
     */
    private MockObject $pathsHelper;

    /**
     * @var MockObject&Environment
     */
    private MockObject $twig;

    /**
     * @var MockObject&FilesystemLoader
     */
    private MockObject $loader;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $coreParameterHelper;

    /**
     * @var MockObject&BuilderIntegrationsHelper
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

        $this->pathsHelper->expects($this->once())->method('getSystemPath')
            ->with('themes', true)
            ->willReturn(__DIR__.'/resource/themes');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.core.theme.missing.files', $this->anything(), 'validators')
            ->willReturnCallback(
                function ($key, array $parameters): void {
                    $this->assertStringContainsString('config.json', (string) $parameters['%files%']);
                }
            );

        $this->themeHelper->install(__DIR__.'/resource/themes/missing-config.zip');
    }

    public function testExceptionThrownWithMissingMessage(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->pathsHelper->expects($this->once())->method('getSystemPath')
            ->with('themes', true)
            ->willReturn(__DIR__.'/resource/themes');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.core.theme.missing.files', $this->anything(), 'validators')
            ->willReturnCallback(
                function ($key, array $parameters): void {
                    $this->assertStringContainsString('message.html.twig', (string) $parameters['%files%']);
                }
            );

        $this->themeHelper->install(__DIR__.'/resource/themes/missing-message.zip');
    }

    public function testExceptionThrownWithMissingFeature(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->pathsHelper->expects($this->once())->method('getSystemPath')
            ->with('themes', true)
            ->willReturn(__DIR__.'/resource/themes');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.core.theme.missing.files', $this->anything(), 'validators')
            ->willReturnCallback(
                function ($key, array $parameters): void {
                    $this->assertStringContainsString('page.html.twig', (string) $parameters['%files%']);
                }
            );

        $this->themeHelper->install(__DIR__.'/resource/themes/missing-feature.zip');
    }

    public function testThemeIsInstalled(): void
    {
        $fs = new Filesystem();
        $fs->copy(__DIR__.'/resource/themes/good.zip', __DIR__.'/resource/themes/good-tmp.zip');

        $this->pathsHelper->expects($this->once())->method('getSystemPath')
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

        $this->pathsHelper->expects($this->exactly(46))->method('getSystemPath')
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
        $this->assertSame('@themes/_1-2-1-2-column/html/page.html.twig', $template);
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

        $this->pathsHelper->expects($this->exactly(46))->method('getSystemPath')
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
        $this->assertNotSame('@themes/nature/page.html.twig', $template);
        $this->assertNotSame('@themes/goldstar/page.html.twig', $template);
        $this->assertStringContainsString('/page.html.twig', $template);
    }

    public function testCopyWithNoNewDirName(): void
    {
        $themeHelper = new ThemeHelper(
            new class() extends PathsHelper {
                public function __construct()
                {
                }

                public function getSystemPath($name, $fullPath = false): string
                {
                    Assert::assertSame('themes', $name);

                    return '/path/to/themes';
                }
            },
            new Environment(new FilesystemLoader()),
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
                public function exists(string|iterable $files): bool
                {
                    return '/path/to/themes/new-theme-name' !== $files;
                }

                /**
                 * @param ?\Traversable<mixed> $iterator
                 * @param mixed[]              $options
                 */
                public function mirror(string $originDir, string $targetDir, ?\Traversable $iterator = null, array $options = []): void
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
            new class() extends Finder {
                /**
                 * @var SplFileInfo[]
                 */
                private array $dirs = [];

                public function __construct()
                {
                }

                public function in(string|array $dirs): static
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
            new class() extends PathsHelper {
                public function __construct()
                {
                }

                public function getSystemPath($name, $fullPath = false): string
                {
                    Assert::assertSame('themes', $name);

                    return '/path/to/themes';
                }
            },
            new Environment(new FilesystemLoader()),
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
                public function exists(string|iterable $files): bool
                {
                    return '/path/to/themes/requested-theme-dir' !== $files;
                }

                /**
                 * @param ?\Traversable<mixed> $iterator
                 * @param array<mixed>         $options
                 */
                public function mirror(string $originDir, string $targetDir, ?\Traversable $iterator = null, array $options = []): void
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
            new class() extends Finder {
                /**
                 * @var SplFileInfo[]
                 */
                private array $dirs = [];

                public function __construct()
                {
                }

                public function in(string|array $dirs): static
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
        $this->builderIntegrationsHelper->expects($this->exactly(6))->method('getBuilder')
            ->willThrowException(new IntegrationNotFoundException());

        $this->pathsHelper->expects($this->exactly(4))->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes('email');
        $this->assertCount(2, $themes);
        $this->assertArrayHasKey('theme-legacy-email', $themes);
        $this->assertArrayHasKey('theme-legacy-all', $themes);

        $themes = $this->themeHelper->getInstalledThemes('page');
        $this->assertCount(1, $themes);
        $this->assertArrayHasKey('theme-legacy-all', $themes);
    }

    public function testCustomThemesAreReturnedForFeatureIfCustomBuilderIsEnabled(): void
    {
        $mockBuilder = $this->createMock(BuilderInterface::class);
        $mockBuilder->expects($this->exactly(6))->method('getName')
            ->willReturn('custom');

        $integration = new Integration();
        $integration->setIsPublished(true);

        $mockBuilder->method('getIntegrationConfiguration')
            ->willReturn($integration);
        $this->builderIntegrationsHelper->method('getBuilder')
            ->willReturn($mockBuilder);

        $this->pathsHelper->expects($this->exactly(4))->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes('page');
        $this->assertCount(2, $themes);
        $this->assertArrayHasKey('theme-custom-builder-all', $themes);
        $this->assertArrayHasKey('theme-custom-builder-page', $themes);

        $themes = $this->themeHelper->getInstalledThemes('email');
        $this->assertCount(1, $themes);
        $this->assertArrayHasKey('theme-custom-builder-all', $themes);
    }

    public function testAllThemesAreReturned(): void
    {
        $this->pathsHelper->expects($this->exactly(5))->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes();
        $this->assertCount(4, $themes);

        // Test that a list of themes are returned by default
        $themeKeys   = array_keys($themes);
        $themeValues = array_values($themes);
        $this->assertSame($themeKeys, $themeValues);
    }

    public function testExtendedThemeDetailsAreReturned(): void
    {
        $this->pathsHelper->expects($this->exactly(5))->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes('all', true);
        $this->assertCount(4, $themes);
        $this->assertArrayHasKey('name', $themes['theme-legacy-email']);
        $this->assertArrayHasKey('dir', $themes['theme-legacy-email']);
    }

    public function testExtendedThemeDetailsWithoutDirectoriesAreReturned(): void
    {
        $this->pathsHelper->expects($this->once())->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $themes = $this->themeHelper->getInstalledThemes('all', true, false, false);
        $this->assertCount(4, $themes);
        $this->assertArrayHasKey('name', $themes['theme-legacy-email']);
        $this->assertArrayNotHasKey('dir', $themes['theme-legacy-email']);
    }

    public function testCachedThemesReturnAsExpected(): void
    {
        $this->builderIntegrationsHelper->expects($this->exactly(6))->method('getBuilder')
            ->willThrowException(new IntegrationNotFoundException());
        $matcher = $this->exactly(2);

        $this->pathsHelper
            ->expects($matcher)
            ->method('getSystemPath')->willReturnCallback(function (...$parameters) use ($matcher): string {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('themes', $parameters[0]);
                    $this->assertTrue($parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('themes', $parameters[0]);
                    $this->assertFalse($parameters[1]);
                }

                return __DIR__.'/resource/themes';
            });

        $themes = $this->themeHelper->getInstalledThemes('all', true, false, false);
        $this->assertCount(4, $themes);
        $this->assertArrayHasKey('name', $themes['theme-legacy-email']);
        $this->assertArrayNotHasKey('dir', $themes['theme-legacy-email']);

        // this should return cached results
        $themes = $this->themeHelper->getInstalledThemes('all', true, false, false);
        $this->assertCount(4, $themes);
        $this->assertArrayHasKey('name', $themes['theme-legacy-email']);
        $this->assertArrayNotHasKey('dir', $themes['theme-legacy-email']);

        $themes = $this->themeHelper->getInstalledThemes('page', true, false, false);
        $this->assertCount(1, $themes);
        $this->assertArrayHasKey('name', $themes['theme-legacy-all']);
        $this->assertArrayNotHasKey('dir', $themes['theme-legacy-all']);

        $themes = $this->themeHelper->getInstalledThemes('page', true, false, true);
        $this->assertCount(1, $themes);
        $this->assertArrayHasKey('name', $themes['theme-legacy-all']);
        $this->assertArrayHasKey('dir', $themes['theme-legacy-all']);
    }

    public function testGetCurrentThemeWillReturnCodeModeIfTheThemeIsCodeMode(): void
    {
        $this->pathsHelper->expects($this->once())->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $this->assertTrue($this->themeHelper->exists('theme-legacy-email'));
    }

    public function testExistsReturnsFalseIfThemeDoesNotExist(): void
    {
        $this->pathsHelper->expects($this->once())->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $this->assertFalse($this->themeHelper->exists('theme-legacy-email-foo'));
    }

    public function testDefaultThemeNotShouldNotGetRemoved(): void
    {
        $this->pathsHelper->expects($this->exactly(7))->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->exactly(5))
            ->method('exists')->willReturn(true);

        $filesystem->expects($this->exactly(4))->method('readFile')->willReturn('{"name": "Test Theme"}');

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
        $this->assertTrue($themeHelper->exists('theme-legacy-email'));
    }

    public function testDeleteThemeThrowsExceptionIfThemeDoesNotExist(): void
    {
        $this->pathsHelper->expects($this->exactly(6))->method('getSystemPath')
            ->willReturn(__DIR__.'/resource/themes');

        $this->expectException(FileNotFoundException::class);
        $this->themeHelper->delete('theme-legacy-email-foo');
    }

    public function testRenderThemeTemplateResolvesRuntimeBackedFiltersInsideSandbox(): void
    {
        $twig = new Environment(new ArrayLoader([
            '@themes/test/html/page.html.twig' => '{{ value|runtime_backed }}',
        ]));
        $twig->addExtension(new ThemeHelperRuntimeBackedFilterExtension());
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            ThemeHelperRuntimeBackedFilterRuntime::class => static fn (): ThemeHelperRuntimeBackedFilterRuntime => new ThemeHelperRuntimeBackedFilterRuntime(),
        ]));

        $themeHelper = new ThemeHelper(
            $this->pathsHelper,
            $twig,
            $this->translator,
            $this->coreParameterHelper,
            new Filesystem(),
            new Finder(),
            $this->builderIntegrationsHelper
        );

        $rendered = $themeHelper->renderThemeTemplate('@themes/test/html/page.html.twig', ['value' => 'runtime ok']);

        $this->assertSame('runtime ok [runtime]', $rendered);
    }
}

final class ThemeHelperRuntimeBackedFilterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('runtime_backed', [ThemeHelperRuntimeBackedFilterRuntime::class, 'transform']),
        ];
    }
}

final class ThemeHelperRuntimeBackedFilterRuntime
{
    public function transform(string $value): string
    {
        return $value.' [runtime]';
    }
}
