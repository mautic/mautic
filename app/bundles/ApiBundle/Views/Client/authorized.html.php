<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th style="width: 50px;"></th>
            <th><?php echo $view['translator']->trans('mautic.core.name'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($clients as $k): ?>
        <tr>
            <td>
                <?php echo $view->render('MauticCoreBundle:Helper:confirm.html.php',
                    [
                        'btnClass'      => 'btn btn-danger btn-xs',
                        'message'       => $view['translator']->trans('mautic.api.client.form.confirmrevoke', ['%name%' => $k->getName()]),
                        'confirmText'   => $view['translator']->trans('mautic.api.client.form.revoke'),
                        'confirmAction' => $view['router']->path('mautic_client_action', ['objectAction' => 'revoke', 'objectId' => $k->getId()]),
                        'iconClass'     => 'fa fa-trash-o',
                        'btnText'       => $view['translator']->trans('mautic.api.client.form.revoke'),
                    ]
                ); ?>
            </td>
            <td><?php echo $k->getName(); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
