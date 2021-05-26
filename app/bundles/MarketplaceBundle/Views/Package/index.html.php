<?php declare(strict_types=1);

$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('mauticContent', 'Package');
$view['slots']->set('headerTitle', $view['translator']->trans('marketplace.title'));
$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php'));
?>
<div class="alert alert-info" role="alert">
    <?php echo $view['translator']->trans('marketplace.beta.warning'); ?>
</div>
<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render(
        'MauticCoreBundle:Helper:list_toolbar.html.php',
        [
            'searchValue' => $searchValue,
            // 'action'      => $currentRoute,
        ]
    ); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>