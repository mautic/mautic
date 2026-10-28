<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Event\ConfigPreSaveEvent;
use Mautic\CoreBundle\Form\Type\ConfigType;
use Mautic\CoreBundle\Helper\LanguageHelper;

final readonly class ConfigSubscriber
{
    public function __construct(
        private LanguageHelper $languageHelper,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ConfigBuilderEvent::class,
        priority: 0
    )]
    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $coreParams = $event->getParametersFromConfig('MauticCoreBundle');
        unset($coreParams['theme']);
        unset($coreParams['theme_import_allowed_extensions']);
        $event->addForm([
            'bundle'     => 'CoreBundle',
            'formType'   => ConfigType::class,
            'formAlias'  => 'coreconfig',
            'formTheme'  => '@MauticCore/FormTheme/Config/config_layout.html.twig',
            'parameters' => $coreParams,
        ]);
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ConfigPreSaveEvent::class,
        priority: 0
    )]
    public function onConfigBeforeSave(ConfigEvent $event): void
    {
        $values = $event->getConfig();

        // Preserve existing value
        $event->unsetIfEmpty('transifex_password');

        // Check if the selected locale has been downloaded already, fetch it if not
        if (!array_key_exists($values['coreconfig']['locale'], $this->languageHelper->getSupportedLanguages())) {
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
