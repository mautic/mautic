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
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => [
                ['onConfigGenerate', 0],
                ['onConfigCompanyGenerate', 0],
            ],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $leadParameters = $event->getParametersFromConfig('MauticLeadBundle');
        unset($leadParameters['company_unique_identifiers_operator']);
        $event->addForm([
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'leadconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => '@MauticLead/FormTheme/Config/_config_companyconfig_widget.html.twig',
            'parameters' => $leadParameters,
        ]);

        $segmentParameters = $event->getParametersFromConfig('MauticLeadBundle');
        unset(
            $segmentParameters['contact_unique_identifiers_operator'],
            $segmentParameters['contact_columns'],
            $segmentParameters['background_import_if_more_rows_than'],
            $segmentParameters['contact_export_in_background']
        );
        $event->addForm([
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'segment_config',
            'formType'   => SegmentConfigType::class,
            'formTheme'  => '@MauticLead/FormTheme/Config/_config_leadconfig_widget.html.twig',
            'parameters' => $segmentParameters,
        ]);
    }

    public function onConfigCompanyGenerate(ConfigBuilderEvent $event): void
    {
        $parameters = $event->getParametersFromConfig('MauticLeadBundle');
        $event->addForm([
            'bundle'     => 'LeadBundle',
            'formAlias'  => 'companyconfig',
            'formType'   => ConfigCompanyType::class,
            'formTheme'  => '@MauticLead/FormTheme/Config/_config_segment_config_widget.html.twig',
            'parameters' => [
                'company_unique_identifiers_operator' => $parameters['company_unique_identifiers_operator'],
            ],
        ]);
    }
}
