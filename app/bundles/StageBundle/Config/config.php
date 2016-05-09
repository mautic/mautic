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
            'mautic_stage_index'              => array(
                'path'       => '/stages/{page}',
                'controller' => 'MauticStageBundle:Stage:index'
            ),
            'mautic_stage_action'             => array(
                'path'       => '/stages/{objectAction}/{objectId}',
                'controller' => 'MauticStageBundle:Stage:execute'
            )
        ),
        'api'  => array(
            'mautic_api_getstages'   => array(
                'path'       => '/points',
                'controller' => 'MauticStageBundle:Api\StageApi:getEntities'
            ),
            'mautic_api_getstage'    => array(
                'path'       => '/stages/{id}',
                'controller' => 'MauticStageBundle:Api\StageApi:getEntity'
            )
        )
    ),

    'menu'     => array(
        'main' => array(
            'mautic.stages.menu.root' => array(
                'id'        => 'mautic_stages_root',
                'iconClass' => 'fa-scale',
                'access'    => array('point:stages:view'),
                'priority'  => 30,
                'children'  => array(
                    'mautic.stage.menu.index'         => array(
                        'route'  => 'mautic_stage_index',
                        'access' => 'stage:stages:view'
                    )
                )
            )
        )
    ),

    'categories' => array(
        'stage' => null
    ),

    'services' => array(
        'events' => array(
            'mautic.stage.subscriber'            => array(
                'class' => 'Mautic\StageBundle\EventListener\StageSubscriber'
            ),
            'mautic.stage.leadbundle.subscriber' => array(
                'class' => 'Mautic\StageBundle\EventListener\LeadSubscriber'
            ),
            'mautic.stage.search.subscriber'     => array(
                'class' => 'Mautic\PointBundle\EventListener\SearchSubscriber'
            ),
            'mautic.point.dashboard.subscriber'  => array(
                'class' => 'Mautic\PointBundle\EventListener\DashboardSubscriber'
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
        )
    )
);
