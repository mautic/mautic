<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ConfigSubscriber implements EventSubscriberInterface
{
    public function __construct(
        // Keep the legacy constructor signature for third-party plugins while
        // retaining the helper for events without an original form snapshot.
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigBeforeSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'bundle'     => 'EmailBundle',
            'formType'   => ConfigType::class,
            'formAlias'  => 'emailconfig',
            'formTheme'  => '@MauticEmail/FormTheme/Config/_config_emailconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticEmailBundle'),
        ]);
    }

    public function onConfigBeforeSave(ConfigEvent $event): void
    {
        $data = $event->getConfig('emailconfig');

        // Get the original form data so that passwords aren't lost. Some
        // third-party producers dispatch CONFIG_PRE_SAVE without a snapshot;
        // in that case use the persisted configuration instead.
        $originalNormData = $event->getOriginalNormData();
        $monitoredEmail   = null === $originalNormData
            ? $this->coreParametersHelper->get('monitored_email', [])
            : ($originalNormData['emailconfig']['parameters']['monitored_email'] ?? []);
        if (isset($data['monitored_email'])) {
            foreach ($data['monitored_email'] as $key => $monitor) {
                if (empty($monitor['password']) && !empty($monitoredEmail[$key]['password'])) {
                    $data['monitored_email'][$key]['password'] = $monitoredEmail[$key]['password'];
                }

                if ('general' != $key) {
                    if (empty($monitor['host']) || empty($monitor['address']) || empty($monitor['folder'])) {
                        // Reset to defaults
                        $data['monitored_email'][$key]['override_settings'] = 0;
                        $data['monitored_email'][$key]['address']           = null;
                        $data['monitored_email'][$key]['host']              = null;
                        $data['monitored_email'][$key]['user']              = null;
                        $data['monitored_email'][$key]['password']          = null;
                        $data['monitored_email'][$key]['encryption']        = '/ssl';
                        $data['monitored_email'][$key]['port']              = '993';
                    }
                }
            }
        }

        $event->setConfig($data, 'emailconfig');
    }
}
