<?php

namespace Mautic\ApiBundle\EventListener;

use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PointBundle\PointEvents;
use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ApiEvent;

class PointSubscriber extends CommonSubscriber
{

    static public function getSubscribedEvents()
    {
        return array(
            PointEvents::POINT_ON_BUILD => array(
                'onPointBuild',
                0
            ),
            ApiEvents::API_CALL_APPLYRULE => array(
                'onApiCallApplyRule',
                0
            )
        );
    }

    public function onPointBuild(PointBuilderEvent $event)
    {
        $action = array(
            'group' => 'mautic.api.actions',
            'label' => 'mautic.api.point.action.call',
            'description' => 'mautic.api.point.action.call_descr',
            'callback' => array(
                '\\Mautic\\ApiBundle\\Helper\\PointEventHelper',
                'validateApiCall'
            ),
            'formType' => 'pointaction_apicall'
        );

        $event->addAction('api.call', $action);
    }

    public function onApiCallApplyRule(ApiEvent $e)
    {
        $this->factory->getModel('point')->triggerAction("api.call", $e, null, $e->getLead());
    }
}
