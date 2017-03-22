<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Symfony\Bundle\FrameworkBundle\Templating\Helper\TranslatorHelper as BaseHelper;

/**
 * Extended TranslatorHelper.
 */
class TranslatorHelper extends BaseHelper
{
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
     * @param      $preferred
     * @param      $alternative
     * @param      $parameters
     * @param null $domain
     * @param null $locale
     *
     * @return string
     */
    public function transConditional($preferred, $alternative, $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->transConditional($preferred, $alternative, $parameters, $domain, $locale);
    }

    /**
     * @return string
     */
    public function getJsLang()
    {
        $this->translator->addResource('mautic', null, $this->translator->getLocale(), 'javascript');

        $messages = $this->translator->getMessages();

        $oldKeys = [
            'chosenChooseOne'     => $this->trans('mautic.core.form.chooseone'),
            'chosenChooseMore'    => $this->trans('mautic.core.form.choosemultiple'),
            'chosenNoResults'     => $this->trans('mautic.core.form.nomatches'),
            'pleaseWait'          => $this->trans('mautic.core.wait'),
            'popupBlockerMessage' => $this->trans('mautic.core.popupblocked'),
        ];

        $jsLang = array_merge($messages['javascript'], $oldKeys);

        return json_encode($jsLang, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
    }
}
