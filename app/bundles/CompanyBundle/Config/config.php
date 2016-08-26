<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes'   => [
        'main' => [
            'mautic_company_index'              => [
                'path'       => '/companies/{page}',
                'controller' => 'MauticCompanyBundle:Company:index'
            ],
            'mautic_company_action'             => [
                'path'       => '/companies/{objectAction}/{objectId}',
                'controller' => 'MauticCompanyBundle:Company:execute'
            ]
        ],
        'api'  => [
            'mautic_api_getcompanies'   => [
                'path'       => '/companies',
                'controller' => 'MauticCompanyBundle:Api\CompanyApi:getEntities'
            ],
            'mautic_api_getcompany'    => [
                'path'       => '/companies/{id}',
                'controller' => 'MauticCompanyBundle:Api\CompanyApi:getEntity'
            ]
        ]
    ],

    'menu'     => [
        'main' => [
            'mautic.companies.menu.index' => [
                'route'  => 'mautic_company_index',
                'iconClass' => 'fa-building-o',
                'access'    => ['company:companies:view'],
                'priority'  => 65
            ]
        ]
    ],
    'services' => [
        'forms'  => [
            'mautic.company.type.form'                  => [
                'class'     => 'Mautic\CompanyBundle\Form\Type\CompanyType',
                'arguments' => 'mautic.factory',
                'alias'     => 'company'
            ]
        ],
        'models' =>  [
            'mautic.company.model.company' => [
                'class' => 'Mautic\CompanyBundle\Model\CompanyModel',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'session'
                ]
            ]
        ]
    ]
];
