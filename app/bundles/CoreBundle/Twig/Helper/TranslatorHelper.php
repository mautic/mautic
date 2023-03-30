<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Translation\Translator;

/**
 * Extended TranslatorHelper.
 *
 * @property Translator $translator
 */
final class TranslatorHelper
{
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
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
    public function hasId($id, $domain = null, $locale = null)
    {
        return $this->translator->hasId($id, $domain, $locale);
    }

    /**
     * Checks for $preferred string existence and returns translation if it does.  Otherwise, returns translation for
     * $alternative.
     *
     * @param array<mixed> $parameters
     */
    public function transConditional(string $preferred, string $alternative, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->transConditional($preferred, $alternative, $parameters, $domain, $locale);
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
