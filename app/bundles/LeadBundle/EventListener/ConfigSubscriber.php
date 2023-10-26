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
        $leadParameters = $event->getParametersFromConfig('MauticLeadBundle');
        unset($leadParameters['company_unique_identifiers_operator']);
        $event->addForm([
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'leadconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticLeadBundle:FormTheme\Config',
            'parameters' => $leadParameters,
        ]);

        $segmentParameters = $event->getParametersFromConfig('MauticLeadBundle');
        unset($segmentParameters['contact_unique_identifiers_operator'], $segmentParameters['contact_columns'], $segmentParameters['background_import_if_more_rows_than']);
        $event->addForm([
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'segment_config',
            'formType'   => SegmentConfigType::class,
            'formTheme'  => 'MauticLeadBundle:FormTheme\Config',
            'parameters' => $segmentParameters,
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
