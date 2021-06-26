<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\LeadBundle\Form\Type\ConfigCompanyType;
use Mautic\LeadBundle\Form\Type\ConfigType;
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
