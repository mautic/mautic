<?php

/*
 * @author      Captivea (QCH)
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'scoring');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.scoring.menu.root'));

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'templateButtons' => [
                'new' => $permissions['point:scoringCategory:create'],
            ],
            'routeBase' => 'scoring',
        ]
    )
);
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render(
        'MauticCoreBundle:Helper:list_toolbar.html.php',
        [
            'searchValue' => $searchValue,
            'searchHelp'  => 'mautic.core.help.searchcommands',
            'action'      => $currentRoute,
        ]
    ); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
