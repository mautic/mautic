<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticLeadBundle:Lead:index.html.php');
}
?>

<div class="pa-md bg-auto">
    <?php if (count($items)): ?>
        <div class="row shuffle-grid">
            <?php
            foreach ($items as $item):
                echo $view->render(
                    'MauticLeadBundle:Lead:grid_card.html.php',
                    [
                        'contact'       => $item,
                        'noContactList' => (isset($noContactList)) ? $noContactList : [],
                    ]
                );
            endforeach;
            ?>
        </div>
    <?php else: ?>
        <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['header' => 'mautic.lead.grid.noresults.header', 'message' => 'mautic.lead.grid.noresults.message']); ?>
        <div class="clearfix"></div>
    <?php endif; ?>
</div>
<?php if (count($items)): ?>
    <div class="panel-footer">
        <?php
        if (!isset($route)):
            $route = (isset($link)) ? $link : 'mautic_contact_index';
        endif;
        if (!isset($routeParameters)):
            $routeParameters = [];
        endif;
        if (isset($objectId)):
            $routeParameters['objectId'] = $objectId;
        endif;

        echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            [
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'baseUrl'    => $view['router']->path($route, $routeParameters),
                'tmpl'       => (!in_array($tmpl, ['grid', 'index'])) ? $tmpl : $indexMode,
                'sessionVar' => (isset($sessionVar)) ? $sessionVar : 'lead',
                'target'     => (!empty($target)) ? $target : '.page-list',
            ]
        );
        ?>
    </div>
<?php endif; ?>
