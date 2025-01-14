<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'pageresult');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.page.result.header.index', [
    '%name%' => $activePage->getName(),
]));

$buttons = [];

$buttons[] = [
    'attr' => [
        'target'      => '_new',
        'data-toggle' => '',
        'class'       => 'btn btn-default btn-nospin',
        'href'        => $view['router']->path('mautic_page_export', ['objectId' => $activePage->getId(), 'format' => 'html']),
    ],
    'btnText'   => $view['translator']->trans('mautic.form.result.export.html'),
    'iconClass' => 'fa fa-file-code-o',
    'primary'   => true,
];

$buttons[] = [
    'attr' => [
        'data-toggle' => '',
        'class'       => 'btn btn-default btn-nospin',
        'href'        => $view['router']->path('mautic_page_export', ['objectId' => $activePage->getId(), 'format' => 'csv']),
    ],
    'btnText'   => $view['translator']->trans('mautic.form.result.export.csv'),
    'iconClass' => 'fa fa-file-text-o',
    'primary'   => true,
];

if (class_exists(Spreadsheet::class)) {
    $buttons[] = [
        'attr' => [
            'data-toggle' => '',
            'class'       => 'btn btn-default btn-nospin',
            'href'        => $view['router']->path('mautic_page_export', ['objectId' => $activePage->getId(), 'format' => 'xlsx']),
        ],
        'btnText'   => $view['translator']->trans('mautic.form.result.export.xlsx'),
        'iconClass' => 'fa fa-file-excel-o',
        'primary'   => true,
    ];
}

$buttons[] =
    [
        'attr' => [
                'class'       => 'btn btn-default',
                'href'        => $view['router']->path('mautic_page_action', ['objectAction' => 'view', 'objectId'=> $activePage->getId()]),
                'data-toggle' => 'ajax',
            ],
        'iconClass' => 'fa fa-remove',
        'btnText'   => $view['translator']->trans('mautic.core.form.close'),
    ];

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', ['customButtons' => $buttons]));
?>

<div class="page-list">
    <?php $view['slots']->output('_content'); ?>
</div>
