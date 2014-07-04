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
    $view->extend('MauticUserBundle:User:index.html.php');
endif;
?>

<div class="table-responsive scrollable body-white padding-sm">
    <table class="table table-hover table-striped table-bordered user-list">
        <thead>
        <tr>
            <th class="col-user-actions"></th>
            <th class="visible-md visible-lg col-user-avatar"></th>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'user',
                'orderBy'    => 'u.lastName, u.firstName, u.username',
                'text'       => 'mautic.user.user.thead.name',
                'class'      => 'visible-md visible-lg col-user-name',
                'default'    => true
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'user',
                'orderBy'    => 'u.username',
                'text'       => 'mautic.user.user.thead.username',
                'class'      => 'col-user-username',
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'user',
                'orderBy'    => 'u.email',
                'text'       => 'mautic.user.user.thead.email',
                'class'      => 'visible-md visible-lg col-user-email'
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'user',
                'orderBy'    => 'r.name',
                'text'       => 'mautic.user.user.thead.role',
                'class'      => 'visible-md visible-lg col-user-role'
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'user',
                'orderBy'    => 'u.id',
                'text'       => 'mautic.user.user.thead.id',
                'class'      => 'visible-md visible-lg col-user-id'
            ));
            ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item):?>
            <tr>
                <td>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                        'item'      => $item,
                        'edit'      => $permissions['edit'],
                        'delete'    => $permissions['delete'],
                        'routeBase' => 'user',
                        'menuLink'  => 'mautic_user_index',
                        'langVar'   => 'user.user'
                    ));
                    ?>
                </td>
                <td class="visible-md visible-lg">
                    <img class="img img-responsive img-thumbnail"
                         src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($item->getEmail()))); ?>?&s=50" />
                </td>
                <td>
                    <?php echo $item->getName(true); ?><br />
                    <em><?php echo $item->getPosition(); ?></em>
                </td>
                <td><?php echo $item->getUsername(); ?></td>
                <td class="visible-md visible-lg">
                    <a href="mailto: <?echo $item->getEmail(); ?>"><?php echo $item->getEmail(); ?></a>
                </td>
                <td class="visible-md visible-lg"><?php echo $item->getRole()->getName(); ?></td>
                <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "items"      => $items,
        "page"       => $page,
        "limit"      => $limit,
        "baseUrl"    =>  $view['router']->generate('mautic_user_index'),
        'sessionVar' => 'user',
        'target'     => '.main-panel-content-wrapper'
    )); ?>
    <div class="footer-margin"></div>
</div>