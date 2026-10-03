<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Translation;

use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator as SymfonyTranslator;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Registers Mautic's translation resource for whichever locale is asked about.
 *
 * Mautic's translations are not files Symfony can discover up front: the 'mautic' loader
 * walks every bundle and the active theme for the locale it is given, and language packs
 * are installed at runtime, so the set of locales is not known when the container is built.
 * The resource therefore has to be registered lazily, just before the catalogue behind it
 * is built.
 *
 * This used to be a loadCatalogue() override on a subclass of FrameworkBundle's Translator,
 * which Symfony 8 closed off by making that class final. It cannot move into
 * {@see Translator} either: that one decorates 'translator' from the outside, so what it
 * wraps may be a debug wrapper rather than the concrete translator, and only the concrete
 * one exposes addResource(). This decorates 'translator.default' instead, which is always
 * the real implementation.
 */
final class MauticResourceTranslator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface, WarmableInterface
{
    /**
     * @var array<string, true>
     */
    private array $registered = [];

    /**
     * @param list<string> $warmupLocales
     */
    public function __construct(
        private readonly SymfonyTranslator&WarmableInterface $translator,
        private readonly array $warmupLocales = [],
    ) {
    }

    private function register(?string $locale): void
    {
        $locale ??= $this->translator->getLocale();

        // en_US is always registered so that it is there to fall back to
        foreach (['en_US', $locale] as $each) {
            if (isset($this->registered[$each])) {
                continue;
            }

            $this->registered[$each] = true;
            $this->translator->addResource('mautic', null, $each, 'messages');
        }
    }

    /**
     * @param array<mixed> $parameters
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $this->register($locale);

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function getCatalogue(?string $locale = null): MessageCatalogueInterface
    {
        $this->register($locale);

        return $this->translator->getCatalogue($locale);
    }

    /**
     * @return MessageCatalogueInterface[]
     */
    public function getCatalogues(): array
    {
        $this->register(null);

        return $this->translator->getCatalogues();
    }

    /**
     * @return string[]
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->register(null);

        // Symfony also builds catalogues for configured locales and their fallbacks.
        // Register every resource before any of those catalogues is cached.
        foreach (array_merge($this->warmupLocales, $this->translator->getFallbackLocales()) as $locale) {
            $this->register($locale);
        }

        return $this->translator->warmUp($cacheDir, $buildDir);
    }

    /**
     * Sits between debug wrappers and the concrete translator, both of which forward these
     * on; without them the chain breaks for anything registering loaders or resources.
     */
    public function addLoader(string $format, LoaderInterface $loader): void
    {
        $this->translator->addLoader($format, $loader);
    }

    public function addResource(string $format, mixed $resource, string $locale, ?string $domain = null): void
    {
        $this->translator->addResource($format, $resource, $locale, $domain);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }
}
