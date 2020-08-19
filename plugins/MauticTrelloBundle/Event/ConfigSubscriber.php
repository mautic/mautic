<?php

namespace MauticPlugin\MauticTrelloBundle\Event;

use Exception;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticTrelloBundle\Form\ConfigType;
use MauticPlugin\MauticTrelloBundle\Integration\TrelloIntegration;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var TrelloIntegration|AbstractIntegration|bool
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
        $this->logger      = $logger;
        $integration       = $integrationHelper->getIntegrationObject('Trello');
        if (!$integration instanceof TrelloIntegration) {
            throw new Exception('No TrelloIntegration instance provided');
        }
        $this->integration = $integration;
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

    /**
     * setup the configuration for Trello.
     */
    public function onConfigGenerate(ConfigBuilderEvent $event): bool
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

        return true;
    }

    /**
     * Prepare values before conig is saved to file.
     *
     * @return void
     */
    public function onConfigSave(ConfigEvent $event)
    {
        /**
         * @var array $values
         */
        $config = $event->getConfig('trello_config');

        // Set updated values
        $event->setConfig($config, 'trello_config');
    }
}
