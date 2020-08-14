<?php

namespace MauticPlugin\MauticTrelloBundle\Event;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticTrelloBundle\Form\ConfigType;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var bool|TrelloIntegration
     */
    protected $integration;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Setup Trello Configuration Subscriber.
     */
    public function __construct(IntegrationHelper $integrationHelper, Logger $logger)
    {
        $this->integration = $integrationHelper->getIntegrationObject('Trello');
        $this->logger      = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        if (!$this->integration->isPublished()) {
            return false;
        }

        $event->addForm(
            [
                'formAlias'  => 'trello_config', // same as in the View filename
                'formTheme'  => 'MauticTrelloBundle:FormTheme\Config',
                'formType'   => ConfigType::class,
                'parameters' => $event->getParametersFromConfig('MauticTrelloBundle'),
            ]
        );
    }

    /**
     * Prepare values before conig is saved to file.
     *
     * @return void
     */
    public function onConfigSave(ConfigEvent $event)
    {
        /** @var array $values */
        $config = $event->getConfig('trello_config');

        // Set updated values
        $event->setConfig($config, 'trello_config');
    }
}
