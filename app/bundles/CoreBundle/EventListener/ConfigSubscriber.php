<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;

/**
 * Class ConfigSubscriber.
 */
class ConfigSubscriber extends CommonSubscriber
{
    /**
     * @var LanguageHelper
     */
    protected $languageHelper;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * ConfigSubscriber constructor.
     *
     * @param LanguageHelper       $languageHelper
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(LanguageHelper $languageHelper, CoreParametersHelper $coreParametersHelper)
    {
        $this->languageHelper       = $languageHelper;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigBeforeSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $coreParams = $event->getParametersFromConfig('MauticCoreBundle');
        unset($coreParams['theme']);
        unset($coreParams['theme_import_allowed_extensions']);
        $event->addForm([
            'bundle'     => 'CoreBundle',
            'formAlias'  => 'coreconfig',
            'formTheme'  => 'MauticCoreBundle:FormTheme\Config',
            'parameters' => $coreParams,
        ]);
    }

    public function onConfigBeforeSave(ConfigEvent $event)
    {
        $values = $event->getConfig();

        // Preserve existing value
        $event->unsetIfEmpty('transifex_password');

        // Check if the selected locale has been downloaded already, fetch it if not
        $installedLanguages = $this->coreParametersHelper->getParameter('supported_languages');

        if (!array_key_exists($values['coreconfig']['locale'], $installedLanguages)) {
            $fetchLanguage = $this->languageHelper->extractLanguagePackage($values['coreconfig']['locale']);

            // If there is an error, fall back to 'en_US' as it is our system default
            if ($fetchLanguage['error']) {
                $values['coreconfig']['locale'] = 'en_US';
                $message                        = 'mautic.core.could.not.set.language';
                $messageVars                    = [];

                if (isset($fetchLanguage['message'])) {
                    $message = $fetchLanguage['message'];
                }

                if (isset($fetchLanguage['vars'])) {
                    $messageVars = $fetchLanguage['vars'];
                }

                $event->setError($message, $messageVars);
            }
        }

        $event->setConfig($values);
    }
}
