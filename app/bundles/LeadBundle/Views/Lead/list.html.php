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

<?php if (count($items)): ?>
<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered" id="leadTable">
        <thead>
            <tr>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'checkall' => 'true',
                    'target'   => '#leadTable',
                ]);

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.lastname, l.firstname, l.company, l.email',
                    'text'       => 'mautic.core.name',
                    'class'      => 'col-lead-name',
                ]);

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.email',
                    'text'       => 'mautic.core.type.email',
                    'class'      => 'col-lead-email visible-md visible-lg',
                ]);

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.city, l.state',
                    'text'       => 'mautic.lead.lead.thead.location',
                    'class'      => 'col-lead-location visible-md visible-lg',
                ]);
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.stage_id',
                    'text'       => 'mautic.lead.stage.label',
                    'class'      => 'col-lead-stage',
                ]);
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.points',
                    'text'       => 'mautic.lead.points',
                    'class'      => 'visible-md visible-lg col-lead-points',
                ]);

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.last_active',
                    'text'       => 'mautic.lead.lastactive',
                    'class'      => 'col-lead-lastactive visible-md visible-lg',
                    'default'    => true,
                ]);

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.id',
                    'text'       => 'mautic.core.id',
                    'class'      => 'col-lead-id visible-md visible-lg',
                ]);
                ?>
            </tr>
        </thead>
        <tbody>
        <?php echo $view->render('MauticLeadBundle:Lead:list_rows.html.php', [
            'items'         => $items,
            'security'      => $security,
            'currentList'   => $currentList,
            'permissions'   => $permissions,
            'noContactList' => $noContactList,
        ]); ?>
        </tbody>
    </table>
</div>
<div class="panel-footer">
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', [
        'totalItems' => $totalItems,
        'page'       => $page,
        'limit'      => $limit,
        'menuLinkId' => 'mautic_contact_index',
        'baseUrl'    => $view['router']->path('mautic_contact_index'),
        'tmpl'       => $indexMode,
        'sessionVar' => 'lead',
    ]); ?>
</div>
<?php else: ?>
<?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
