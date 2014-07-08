<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
//Check to see if the entire page should be displayed or just main content
if ($tmpl == 'index'):
    $view->extend('MauticUserBundle:Role:index.html.php');
endif;
?>

<div class="table-responsive scrollable body-white padding-sm">
    <table class="table table-hover table-striped table-bordered role-list">
        <thead>
        <tr>
            <th class="col-role-actions"></th>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'role',
                'orderBy'    => 'r.name',
                'text'       => 'mautic.user.role.thead.name',
                'class'      => 'col-role-name',
                'default'    => true
            ));
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'role',
                'orderBy'    => 'r.description',
                'text'       => 'mautic.user.role.thead.description',
                'class'      => 'visible-md visible-lg col-role-desc'
            ));
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'role',
                'orderBy'    => 'r.id',
                'text'       => 'mautic.user.role.thead.id',
                'class'      => 'visible-md visible-lg col-role-id'
            ));
            ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                        'item'      => $item,
                        'edit'      => $permissions['edit'],
                        'delete'    => $permissions['delete'],
                        'routeBase' => 'user',
                        'menuLink'  => 'mautic_role_index',
                        'langVar'   => 'user.role',
                        'pull'      => 'left'
                    ));
                    ?>
                </td>
                <td>
                    <a href="<?php echo $view['router']->generate('mautic_user_index',
                        array("search" => $view['translator']->trans('mautic.user.user.searchcommand.role') . ':' .  $item->getName())); ?>"
                       data-toggle="ajax">
                        <?php echo $item->getName(); ?>
                    </a>
                </td>
                <td class="visible-md visible-lg"><?php echo $item->getDescription(); ?></td>
                <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "items"      => $items,
        "page"       => $page,
        "limit"      => $limit,
        "baseUrl"    =>  $view['router']->generate('mautic_role_index'),
        'sessionVar' => 'role',
        'target'     => '.main-panel-content-wrapper'
    )); ?>
    <div class="footer-margin"></div>
</div>