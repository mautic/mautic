<?php

/*
 * @copyright   2022 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MessengerBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\MessengerBundle\Form\Type\ConfigType;
use Mautic\MessengerBundle\Helper\DsnDoctrineConvertor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    private array $tempFields = [
        'messenger_transport',
    ];

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
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
        $event->addTemporaryFields($this->tempFields);

        $event->addForm([
            'bundle'     => 'MessengerBundle',
            'formAlias'  => 'messengerconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticMessengerBundle:FormTheme\Config',
            'parameters' => $this->getParameters($event),
        ]);
    }

    public function onConfigBeforeSave(ConfigEvent $event)
    {
        $data = $event->getConfig('messengerconfig');
        $data['messenger_dsn'] = DsnDoctrineConvertor::convertArrayToDsnString($data);

        foreach ($this->tempFields as $tempField) {
            unset($data[$tempField]);
        }

        $event->setConfig($data, 'messengerconfig');
    }

    private function getParameters(ConfigBuilderEvent $event): array
    {
        $parameters = $event->getParametersFromConfig('MauticMessengerBundle');
        $loadedParameters = $this->coreParametersHelper->all();

        if (! empty($loadedParameters['messenger_dsn'])) {
            $messengerParameters = DsnDoctrineConvertor::convertDsnToArray($loadedParameters['messenger_dsn']);
            $parameters = array_merge($parameters, $messengerParameters);
        }

        return $parameters;
    }
}
