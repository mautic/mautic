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

<div class="table-responsive body-white padding-sm">
    <table class="table table-hover table-striped table-bordered user-list">
        <thead>
        <tr>
            <th class="visible-md visible-lg"></th>
            <?php
            echo $view->render('MauticCoreBundle:Table:tableheader.html.php', array(
                'entity'  => 'user',
                'orderBy' => 'u.lastName, u.firstName, u.username',
                'text'    => 'mautic.user.user.thead.name',
                'default' => true
            ));

            echo $view->render('MauticCoreBundle:Table:tableheader.html.php', array(
                'entity'  => 'user',
                'orderBy' => 'u.username',
                'text'    => 'mautic.user.user.thead.username'
            ));

            echo $view->render('MauticCoreBundle:Table:tableheader.html.php', array(
                'entity'  => 'user',
                'orderBy' => 'u.email',
                'text'    => 'mautic.user.user.thead.email',
                'class'   => 'visible-md visible-lg'
            ));

            echo $view->render('MauticCoreBundle:Table:tableheader.html.php', array(
                'entity'  => 'user',
                'orderBy' => 'r.name',
                'text'    => 'mautic.user.user.thead.role',
                'class'   => 'visible-md visible-lg'
            ));

            echo $view->render('MauticCoreBundle:Table:tableheader.html.php', array(
                'entity'  => 'user',
                'orderBy' => 'u.id',
                'text'    => 'mautic.user.user.thead.id',
                'class'   => 'visible-md visible-lg'
            ));
            ?>
            <th style="width: 75px;"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item):?>
            <tr>
                <td class="visible-md visible-lg" style="width: 75px;">
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
                <td>
                    <?php if ($permissions['edit']): ?>
                        <a class="btn btn-primary btn-xs"
                           href="<?php echo $view['router']->generate('mautic_user_action',
                               array("objectAction" => "edit", "objectId" => $item->getId())); ?>"
                           data-toggle="ajax"
                           data-menu-link="#mautic_user_index">
                            <i class="fa fa-pencil-square-o"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($permissions['delete']): ?>
                        <a class="btn btn-danger btn-xs" href="javascript:void(0);"
                           onclick="Mautic.showConfirmation('<?php echo $view->escape($view["translator"]->trans("mautic.user.user.form.confirmdelete", array("%user%" => $item->getName() . " (" . $item->getId() . ")")), 'js'); ?>','<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>','executeAction',['<?php echo $view['router']->generate('mautic_user_action', array("objectAction" => "delete", "objectId" => $item->getId())); ?>','#mautic_user_index'],'<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
                            <i class="fa fa-trash-o"></i>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo $view->render('MauticCoreBundle:Default:pagination.html.php', array(
        "items"   => $items,
        "page"    => $page,
        "limit"   => $limit,
        "baseUrl" =>  $view['router']->generate('mautic_user_index')
    )); ?>
</div>