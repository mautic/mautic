<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Form\Type\IntegrationsListType;
use Mautic\PluginBundle\Helper\EventHelper;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\PointEvents;

/**
 * Class PointSubscriber.
 */
class PointSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PointEvents::TRIGGER_ON_BUILD => ['onTriggerBuild', 0],
        ];
    }

    /**
     * @param TriggerBuilderEvent $event
     */
    public function onTriggerBuild(TriggerBuilderEvent $event)
    {
        $action = [
            'group'     => 'mautic.plugin.point.action',
            'label'     => 'mautic.plugin.actions.push_lead',
            'formType'  => IntegrationsListType::class,
            'formTheme' => 'MauticPluginBundle:FormTheme\Integration',
            'callback'  => [EventHelper::class, 'pushLead'],
        ];

        $event->addEvent('plugin.leadpush', $action);
    }
}
