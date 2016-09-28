<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes'   => array(
        'main' => array(
            'mautic_pointtriggerevent_action' => array(
                'path'       => '/points/triggers/events/{objectAction}/{objectId}',
                'controller' => 'MauticPointBundle:TriggerEvent:execute'
            ),
            'mautic_pointtrigger_index'       => array(
                'path'       => '/points/triggers/{page}',
                'controller' => 'MauticPointBundle:Trigger:index'
            ),
            'mautic_pointtrigger_action'      => array(
                'path'       => '/points/triggers/{objectAction}/{objectId}',
                'controller' => 'MauticPointBundle:Trigger:execute'
            ),
            'mautic_point_index'              => array(
                'path'       => '/points/{page}',
                'controller' => 'MauticPointBundle:Point:index'
            ),
            'mautic_point_action'             => array(
                'path'       => '/points/{objectAction}/{objectId}',
                'controller' => 'MauticPointBundle:Point:execute'
            )
        ),
        'api'  => array(
            'mautic_api_getpoints'   => array(
                'path'       => '/points',
                'controller' => 'MauticPointBundle:Api\PointApi:getEntities'
            ),
            'mautic_api_getpoint'    => array(
                'path'       => '/points/{id}',
                'controller' => 'MauticPointBundle:Api\PointApi:getEntity'
            ),
            'mautic_api_gettriggers' => array(
                'path'       => '/points/triggers',
                'controller' => 'MauticPointBundle:Api\TriggerApi:getEntities'
            ),
            'mautic_api_gettrigger'  => array(
                'path'       => '/points/triggers/{id}',
                'controller' => 'MauticPointBundle:Api\TriggerApi:getEntity'
            )
        )
    ),

    'menu'     => array(
        'main' => array(
            'mautic.points.menu.root' => array(
                'id'        => 'mautic_points_root',
                'iconClass' => 'fa-calculator',
                'access'    => array('point:points:view', 'point:triggers:view'),
                'priority'  => 30,
                'children'  => array(
                    'mautic.point.menu.index'         => array(
                        'route'  => 'mautic_point_index',
                        'access' => 'point:points:view'
                    ),
                    'mautic.point.trigger.menu.index' => array(
                        'route'  => 'mautic_pointtrigger_index',
                        'access' => 'point:triggers:view'
                    )
                )
            )
        )
    ),

    'categories' => array(
        'point' => null
    ),

    'services' => array(
        'events' => array(
            'mautic.point.subscriber'            => array(
                'class' => 'Mautic\PointBundle\EventListener\PointSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog'
                ]
            ),
            'mautic.point.leadbundle.subscriber' => array(
                'class' => 'Mautic\PointBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.point.model.trigger'
                ]
            ),
            'mautic.point.search.subscriber'     => array(
                'class' => 'Mautic\PointBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.point.model.point',
                    'mautic.point.model.trigger'
                ]
            ),
            'mautic.point.dashboard.subscriber'  => array(
                'class' => 'Mautic\PointBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.point.model.point'
                ]
            ),
        ),
        'forms'  => array(
            'mautic.point.type.form'                  => array(
                'class'     => 'Mautic\PointBundle\Form\Type\PointType',
                'arguments' => 'mautic.factory',
                'alias'     => 'point'
            ),
            'mautic.point.type.action'                => array(
                'class' => 'Mautic\PointBundle\Form\Type\PointActionType',
                'alias' => 'pointaction'
            ),
            'mautic.pointtrigger.type.form'           => array(
                'class'     => 'Mautic\PointBundle\Form\Type\TriggerType',
                'arguments' => 'mautic.factory',
                'alias'     => 'pointtrigger'
            ),
            'mautic.pointtrigger.type.action'         => array(
                'class' => 'Mautic\PointBundle\Form\Type\TriggerEventType',
                'alias' => 'pointtriggerevent'
            ),
            'mautic.point.type.genericpoint_settings' => array(
                'class' => 'Mautic\PointBundle\Form\Type\GenericPointSettingsType',
                'alias' => 'genericpoint_settings'
            )
        ),
        'models' =>  array(
            'mautic.point.model.point' => array(
                'class' => 'Mautic\PointBundle\Model\PointModel',
                'arguments' => array(
                    'session',
                    'mautic.helper.ip_lookup',
                    'mautic.lead.model.lead'
                )
            ),
            'mautic.point.model.triggerevent' => array(
                'class' => 'Mautic\PointBundle\Model\TriggerEventModel'
            ),
            'mautic.point.model.trigger' => array(
                'class' => 'Mautic\PointBundle\Model\TriggerModel',
                'arguments' => array(
                    'mautic.helper.ip_lookup',
                    'mautic.lead.model.lead',
                    'mautic.point.model.triggerevent'
                )
            )
        )
    )
);
