<?php

/*
 * @author      Captivea (QCH)
 */

return [
    'name'        => 'Multi scoring',
    'description' => 'do stuff',
    'version'     => '0.1',
    'author'      => 'Captivea',
    'routes' => [
        'main' => [
            'mautic_scoring_index' => [
                'path'       => '/scoring',
                'controller' => 'MauticScoringBundle:ScoringCategory:index',
            ],
            'mautic_scoring_action' => [
                'path'       => '/scoring/{objectAction}/{objectId}',
                'controller' => 'MauticScoringBundle:ScoringCategory:execute',
            ],
        ],
        'api' => [
            'mautic_api_scoringstandard' => [
                'standard_entity' => true,
                'name'            => 'scoring',
                'path'            => '/scoring',
                'controller'      => 'MauticScoringBundle:Api\ScoringCategoryApi',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'mautic.scoring.index' => [
                'route'    => 'mautic_scoring_index',
                'parent'   => 'mautic.points.menu.root',
                'priority' => 2,
                'access'   => 'point:scoringCategory:view',
            ],
        ],
    ],
    'services' => [
        'events' => [],
        'forms' => [
            'mautic.scoring.type.scoringcategory_list' => [
                'class'     => 'Mautic\ScoringBundle\Form\Type\ScoringCategoryListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'scoringcategory_list',
            ],
        ],
        'models' => [
            'mautic.scoring.model.scoringCategory' => [
                'class'     => 'Mautic\ScoringBundle\Model\ScoringCategoryModel',
                'arguments' => [

                ],
            ],
        ],
    ],
];
