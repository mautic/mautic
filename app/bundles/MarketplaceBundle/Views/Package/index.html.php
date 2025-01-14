<?php declare(strict_types=1);

use Mautic\MarketplaceBundle\Service\RouteProvider;

$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('mauticContent', 'Package');
$view['slots']->set('headerTitle', $view['translator']->trans('marketplace.title'));
$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', [
    'customButtons' => [
        [
            'attr' => [
                'class'       => 'btn btn-default btn-nospin',
                'data-toggle' => 'ajax',
                'href'        => $view['router']->path(RouteProvider::ROUTE_CLEAR_CACHE),
            ],
            'iconClass' => 'fa fa-trash',
            'btnText'   => 'marketplace.clear.cache',
            'tooltip'   => 'marketplace.clear.cache.tooltip',
        ],
    ],
]));

if (true === $isComposerEnabled):
?>
<div class="alert alert-info" role="alert">
    <?php echo $view['translator']->trans('marketplace.beta.warning'); ?>
</div>
<?php else: ?>
<div class="alert alert-warning" role="alert">
    <?php echo $view['translator']->trans('marketplace.composer.required'); ?>
</div>
<?php endif; ?>
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