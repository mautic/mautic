<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$app->getRequest()->isXmlHttpRequest()):
    //load base template
    $view->extend('MauticCoreBundle:Default:base.html.php');
endif;
?>
<div class="main-panel-header">
    <h1><?php echo $view['translator']->trans('mautic.users.page.header'); ?></h1>
</div>

<div class="table-responsive white-background">
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th><?php echo $view['translator']->trans('mautic.users.thead.name'); ?></th>
                <th><?php echo $view['translator']->trans('mautic.users.thead.username'); ?></th>
                <th><?php echo $view['translator']->trans('mautic.users.thead.email'); ?></th>
                <th><?php echo $view['translator']->trans('mautic.users.thead.role'); ?></th>
                <th><?php echo $view['translator']->trans('mautic.users.thead.id'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo $u->getFullName(true); ?></td>
                <td><?php echo $u->getUsername(); ?></td>
                <td><?php echo $u->getEmail(); ?></td>
                <td></td>
                <td><?php echo $u->getId(); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>