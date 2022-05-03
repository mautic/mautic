<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\LeadBundle\Form\Type\ConfigCompanyType;
use Mautic\LeadBundle\Form\Type\ConfigType;
use Mautic\LeadBundle\Form\Type\SegmentConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => [
                ['onConfigGenerate', 0],
                ['onConfigCompanyGenerate', 0],
            ],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $parameters = $event->getParametersFromConfig('MauticLeadBundle');
        unset($parameters['company_unique_identifiers_operator']);
        $event->addForm([
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'leadconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticLeadBundle:FormTheme\Config',
            'parameters' => $parameters,
        ]);

        $event->addForm([
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'segment_config',
            'formType'   => SegmentConfigType::class,
            'formTheme'  => 'MauticLeadBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticLeadBundle'),
        ]);
    }

    public function onConfigCompanyGenerate(ConfigBuilderEvent $event)
    {
        $parameters = $event->getParametersFromConfig('MauticLeadBundle');
        $event->addForm([
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'companyconfig',
            'formType'   => ConfigCompanyType::class,
            'formTheme'  => 'MauticLeadBundle:FormTheme\Config',
            'parameters' => [
                'company_unique_identifiers_operator' => $parameters['company_unique_identifiers_operator'],
            ],
        ]);
    }
}
