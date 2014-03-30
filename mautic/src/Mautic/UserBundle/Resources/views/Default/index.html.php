<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view["slots"]->set("headerTitle", $view['translator']->trans('mautic.users.header'));
$currentUrl = $app->getRequest()->getRequestUri();
?>
<div class="table-responsive white-background">
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th>
                    <a href="javascript: void(0);" onclick="Mautic.reorderTableData(
                        'user',
                        'u.lastName, u.firstName, u.username');">
                        <span><?php echo $view['translator']->trans('mautic.users.thead.name'); ?></span>
                    </a>
                </th>
                <th>
                    <a href="javascript: void(0);" onclick="Mautic.reorderTableData('user', 'u.username');">
                        <span><?php echo $view['translator']->trans('mautic.users.thead.username'); ?></span>
                    </a>
                </th>
                <th class="visible-md visible-lg">
                    <a href="javascript: void(0);" onclick="Mautic.reorderTableData('user', 'u.email');">
                        <span><?php echo $view['translator']->trans('mautic.users.thead.email'); ?></span>
                    </a>
                </th>
                <th class="visible-md visible-lg">
                    <?php //@TODO add role id once roles are implemented ?>
                    <a href="javascript: void(0);" onclick="Mautic.reorderTableData('user', 'u.id');">
                        <?php echo $view['translator']->trans('mautic.users.thead.role'); ?>
                    </a>
                </th>
                <th class="visible-md visible-lg">
                    <a href="javascript: void(0);" onclick="Mautic.reorderTableData('user', 'u.id');">
                        <span><?php echo $view['translator']->trans('mautic.users.thead.id'); ?></span>
                    </a>
                </th>
                <th style="width: 75px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo $u->getFullName(true); ?></td>
                <td><?php echo $u->getUsername(); ?></td>
                <td class="visible-md visible-lg"><?php echo $u->getEmail(); ?></td>
                <td class="visible-md visible-lg"></td>
                <td class="visible-md visible-lg"><?php echo $u->getId(); ?></td>
                <td>
                    <button class="btn btn-primary btn-xs"
                            onclick="Mautic.loadMauticContent('<?php echo $view['router']->generate('mautic_user_action',
                                array("objectAction" => "edit", "objectId" => $u->getId())); ?>', '#mautic_user_index');">
                        <i class="fa fa-pencil-square-o"></i>
                    </button>
                    <button class="btn btn-danger btn-xs"
                            onclick="Mautic.showConfirmation(
                                '<?php echo $view["translator"]->trans("mautic.user.form.confirmdelete",
                                    array("%user%" => $u->getFullName() . " (" . $u->getId() . ")")
                                ); ?>',
                                '<?php echo $view["translator"]->trans("mautic.form.delete"); ?>',
                                'executeAction',
                                [
                                    '<?php echo $view['router']->generate('mautic_user_action',
                                        array("objectAction" => "delete", "objectId" => $u->getId())); ?>',
                                    '#mautic_user_index'
                                ],
                                '<?php echo $view["translator"]->trans("mautic.form.cancel"); ?>',
                                '',
                                []
                            );">
                        <i class="fa fa-trash-o"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo $view->render('MauticCoreBundle:Default:pagination.html.php', array(
        "items"   => $users,
        "page"    => $page,
        "limit"   => $limit,
        "baseUrl" =>  $view['router']->generate('mautic_user_index')
    )); ?>
</div>