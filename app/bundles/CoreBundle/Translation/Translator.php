<?php

namespace Mautic\CoreBundle\Translation;

use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extended Translator service.
 */
class Translator implements TranslatorInterface, WarmableInterface, TranslatorBagInterface, LocaleAwareInterface
{
    /**
     * @var TranslatorInterface&WarmableInterface&TranslatorBagInterface&LocaleAwareInterface
     */
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        if (!$translator instanceof WarmableInterface) {
            throw new \InvalidArgumentException('Passed $translator must implement '.WarmableInterface::class);
        }

        if (!$translator instanceof TranslatorBagInterface) {
            throw new \InvalidArgumentException('Passed $translator must implement '.TranslatorBagInterface::class);
        }

        if (!$translator instanceof LocaleAwareInterface) {
            throw new \InvalidArgumentException('Passed $translator must implement '.LocaleAwareInterface::class);
        }

        $this->translator = $translator;
    }

    /**
     * @param array<mixed> $parameters
     */
    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function warmUp(string $cacheDir): array
    {
        $this->translator->warmUp($cacheDir);

        return [];
    }

    public function getCatalogue($locale = null): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    public function getCatalogues(): array
    {
        return $this->translator->getCatalogues();
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }

    /**
     * Check if the specified message ID exists.
     *
     * @param string      $id     The message id (may also be an object that can be cast to string)
     * @param string|null $domain The domain for the message or null to use the default
     * @param string|null $locale The locale or null to use the default
     *
     * @return bool true if the message has a translation, false otherwise
     */
    public function hasId(string $id, ?string $domain = null, ?string $locale = null): bool
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        return $this->getCatalogue($locale)->has($id, $domain);
    }

    /**
     * Checks for $preferred string existence and returns translation if it does.  Otherwise, returns translation for
     * $alternative.
     *
     * @param array<mixed> $parameters
     */
    public function transConditional(string $preferred, string $alternative, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        if ($this->hasId($preferred, $domain, $locale)) {
            return $this->trans($preferred, $parameters, $domain, $locale);
        }

        return $this->trans($alternative, $parameters, $domain, $locale);
    }

    public function getJsLang(): string
    {
        $defaultMessages = $this->translator->getCatalogue('en_US')->all('javascript');
        $messages        = $this->translator->getCatalogue()->all('javascript');

        $oldKeys = [
            'chosenChooseOne'     => $this->translator->trans('mautic.core.form.chooseone'),
            'chosenChooseMore'    => $this->translator->trans('mautic.core.form.choosemultiple'),
            'chosenNoResults'     => $this->translator->trans('mautic.core.form.nomatches'),
            'pleaseWait'          => $this->translator->trans('mautic.core.wait'),
            'popupBlockerMessage' => $this->translator->trans('mautic.core.popupblocked'),
        ];
        $jsLang = array_merge($defaultMessages, $messages, $oldKeys);

        return json_encode($jsLang, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
    }
}
