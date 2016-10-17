<?php

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ApiEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\PointEvents;

class PointSubscriber extends CommonSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            PointEvents::POINT_ON_BUILD => [
                'onPointBuild',
                0,
            ],
            ApiEvents::API_CALL_APPLYRULE => [
                'onApiCallApplyRule',
                0,
            ],
        ];
    }

    public function onPointBuild(PointBuilderEvent $event)
    {
        $action = [
            'group'       => 'mautic.api.actions',
            'label'       => 'mautic.api.point.action.call',
            'description' => 'mautic.api.point.action.call_descr',
            'callback'    => [
                '\\Mautic\\ApiBundle\\Helper\\PointEventHelper',
                'validateApiCall',
            ],
            'formType' => 'pointaction_apicall',
        ];

        $event->addAction('api.call', $action);
    }

    public function onApiCallApplyRule(ApiEvent $e)
    {
        $this->factory->getModel('point')->triggerAction('api.call', $e, null, $e->getLead());
    }
}
