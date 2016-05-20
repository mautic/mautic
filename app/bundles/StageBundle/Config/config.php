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
                'path'       => '/stages',
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
            'mautic.stages.menu.index' => array(
                'route'  => 'mautic_stage_index',
                'iconClass' => 'fa-tachometer',
                'access'    => array('stage:stages:view'),
                'priority'  => 25
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
                'class' => 'Mautic\StageBundle\EventListener\SearchSubscriber'
            ),
            'mautic.stage.dashboard.subscriber'  => array(
                'class' => 'Mautic\StageBundle\EventListener\DashboardSubscriber'
            ),
        ),
        'forms'  => array(
            'mautic.stage.type.form'                  => array(
                'class'     => 'Mautic\StageBundle\Form\Type\StageType',
                'arguments' => 'mautic.factory',
                'alias'     => 'stage'
            ),
            'mautic.stage.type.action'                => array(
                'class' => 'Mautic\StageBundle\Form\Type\StageActionType',
                'alias' => 'stageaction'
            )
        )
    )
);
