<?php

namespace Mautic\MessengerBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\MessengerBundle\Form\Type\ConfigType;
use Mautic\MessengerBundle\Helper\DsnDoctrineConvertor;
use Mautic\MessengerBundle\Helper\MessengerDsnConvertor;
use Mautic\MessengerBundle\Model\MessengerTransportType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var array<string>
     */
    private array $tempFields = [
        'messenger_transport',
        'messenger_host',
        'messenger_port',
        'messenger_user',
        'messenger_password',
    ];

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    private MessengerTransportType $transportType;

    public function __construct(CoreParametersHelper $coreParametersHelper, MessengerTransportType $transportType)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->transportType        = $transportType;
    }

    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigBeforeSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addTemporaryFields($this->tempFields);

        $event->addForm([
            'bundle'     => 'MessengerBundle',
            'formAlias'  => 'messengerconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticMessengerBundle:FormTheme\Config',
            'parameters' => $this->getParameters($event),
        ]);
    }

    public function onConfigBeforeSave(ConfigEvent $event): void
    {
        $data = $event->getConfig('messengerconfig');
        if ('sync' === $data['messenger_type']) {
            $data['messenger_dsn'] = 'sync://';
        } else {
            if ('doctrine' === $data['messenger_transport']) {
                $data['messenger_dsn'] = DsnDoctrineConvertor::convertArrayToDsnString($data);
            } else {
                $data['messenger_dsn'] = MessengerDsnConvertor::convertArrayToDsnString($data, $this->transportType->getTransportDsnConvertors());
            }
        }

        foreach ($this->tempFields as $tempField) {
            unset($data[$tempField]);
        }
        $event->setConfig($data, 'messengerconfig');
    }

    /**
     * @return array<string>
     */
    private function getParameters(ConfigBuilderEvent $event): array
    {
        $parameters       = $event->getParametersFromConfig('MauticMessengerBundle');
        $loadedParameters = $this->coreParametersHelper->all();
        if (!empty($loadedParameters['messenger_dsn'])) {
            $messengerParameters = MessengerDsnConvertor::convertDsnToArray($loadedParameters['messenger_dsn']);
            $parameters          = array_merge($parameters, $messengerParameters);
        }

        return $parameters;
    }
}
