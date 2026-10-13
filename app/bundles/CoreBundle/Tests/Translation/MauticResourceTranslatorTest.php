<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Translation;

use Mautic\CoreBundle\DependencyInjection\Compiler\TranslationLoaderPass;
use Mautic\CoreBundle\Translation\MauticResourceTranslator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

final class MauticResourceTranslatorTest extends TestCase
{
    /**
     * @param list<string>                $enabledLocales
     * @param array<string, list<string>> $resourceFiles
     * @param list<string>                $fallbackLocales
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('warmupLocalesProvider')]
    public function testWarmupCachesMauticTranslations(array $enabledLocales, array $resourceFiles, array $fallbackLocales): void
    {
        $cacheDir = sys_get_temp_dir().'/mautic-translation-warmup-'.bin2hex(random_bytes(8));
        $options  = ['cache_dir' => $cacheDir, 'debug' => false, 'resource_files' => $resourceFiles];

        $container = new ContainerBuilder();
        $container->register('translator.default', Translator::class)
            ->setArgument(4, $options)
            ->setArgument(5, $enabledLocales);
        (new TranslationLoaderPass())->process($container);
        $warmupLocales = $container->getDefinition('translator.mautic_resource')->getArgument(1);

        $loader = new class() implements LoaderInterface {
            public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
            {
                return new MessageCatalogue($locale, [$domain => ['warmup.probe' => 'de' === $locale ? 'Deutsch' : 'English']]);
            }
        };

        $createTranslator = static function () use ($options, $enabledLocales, $fallbackLocales, $warmupLocales, $loader): MauticResourceTranslator {
            $translator = new Translator(new Container(), new MessageFormatter(), 'en_US', [], $options, $enabledLocales);
            $translator->setFallbackLocales($fallbackLocales);
            $translator->addLoader('mautic', $loader);

            return new MauticResourceTranslator($translator, $warmupLocales);
        };

        try {
            $translator = $createTranslator();
            $translator->warmUp($cacheDir);

            Assert::assertSame('Deutsch', $translator->trans('warmup.probe', [], null, 'de'));
            Assert::assertSame('Deutsch', $createTranslator()->trans('warmup.probe', [], null, 'de'));
        } finally {
            (new Filesystem())->remove($cacheDir);
        }
    }

    /**
     * @return iterable<string, array{list<string>, array<string, list<string>>, list<string>}>
     */
    public static function warmupLocalesProvider(): iterable
    {
        yield 'enabled locale' => [['en_US', 'de'], [], ['en_US']];
        yield 'discovered locale' => [[], ['de' => []], ['en_US']];
        yield 'fallback locale' => [[], [], ['de', 'en_US']];
        yield 'fallback with enabled locales' => [['en_US'], [], ['de', 'en_US']];
    }
}
