<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;

/**
 * Class ConfigSubscriber
 *
 * @package Mautic\CoreBundle\EventListener
 */
class ConfigSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            ConfigEvents::CONFIG_ON_GENERATE    => array('onConfigGenerate', 0),
            ConfigEvents::CONFIG_PRE_SAVE       => array('onConfigBeforeSave', 0)
        );
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addForm(array(
            'bundle'        => 'CoreBundle',
            'formAlias'     => 'coreconfig',
            'formTheme'     => 'MauticCoreBundle:FormTheme\Config',
            'parameters'    => $event->getParametersFromConfig('MauticCoreBundle')
        ));
    }

    public function onConfigBeforeSave(ConfigEvent $event)
    {
        $values = $event->getConfig();

        // Preserve existing value
        $event->unsetIfEmpty('transifex_password');

        // Check if the selected locale has been downloaded already, fetch it if not
        $installedLanguages = $this->factory->getParameter('supported_languages');

        if (!array_key_exists($values['coreconfig']['locale'], $installedLanguages)) {
            /** @var \Mautic\CoreBundle\Helper\LanguageHelper $languageHelper */
            $languageHelper = $this->factory->getHelper('language');

            $fetchLanguage = $languageHelper->extractLanguagePackage($values['coreconfig']['locale']);

            // If there is an error, fall back to 'en_US' as it is our system default
            if ($fetchLanguage['error']) {
                $values['coreconfig']['locale'] = 'en_US';
                $message = 'mautic.core.could.not.set.language';
                $messageVars = array();

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
