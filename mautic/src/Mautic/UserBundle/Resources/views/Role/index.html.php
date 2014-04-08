<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view["slots"]->set("headerTitle", $view['translator']->trans('mautic.user.role.header.index'));
?>


<?php if ($permissions['create']): ?>
    <?php $view["slots"]->start("actions"); ?>
    <li><a href="javacript: void(0);"
           onclick="return Mautic.loadMauticContent('<?php echo $this->container->get('router')->generate(
               'mautic_role_action', array("objectAction" => "new")); ?>', '#mautic_role_index'); ">
            <?php echo $view["translator"]->trans("mautic.user.role.menu.new"); ?>
        </a>
    </li>
    <?php $view["slots"]->stop(); ?>
<?php endif; ?>

<?php
$view["slots"]->set("filterInput",
    $view->render('MauticCoreBundle:Form:filter.html.php',
        array(
            'filterUri'    => $this->container->get('router')->generate('mautic_role_index'),
            'filterName'   => 'filter-role',
            'filterValue'  => $filterValue
        )
    )
);
?>

<div class="table-responsive white-background">
    <table class="table table-hover table-striped table-bordered">
        <thead>
            <tr>
                <?php
                echo $view->render('MauticCoreBundle:Table:tableheader.html.php', array(
                    'entity'  => 'role',
                    'orderBy' => 'r.name',
                    'text'    => 'mautic.user.role.thead.name',
                    'default' => true
                ));
                echo $view->render('MauticCoreBundle:Table:tableheader.html.php', array(
                    'entity'  => 'role',
                    'orderBy' => 'r.description',
                    'text'    => 'mautic.user.role.thead.description',
                    'class'   => 'visible-md visible-lg'
                ));
                echo $view->render('MauticCoreBundle:Table:tableheader.html.php', array(
                    'entity'  => 'role',
                    'orderBy' => 'r.id',
                    'text'    => 'mautic.user.role.thead.id',
                    'class'   => 'visible-md visible-lg'
                ));
                ?>
                <th style="width: 75px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo $item->getName(); ?></td>
                <td class="visible-md visible-lg"><?php echo $item->getDescription(); ?></td>
                <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                <td>
                    <?php if ($permissions['edit']): ?>
                    <button class="btn btn-primary btn-xs"
                            onclick="Mautic.loadMauticContent('<?php echo $view['router']->generate('mautic_role_action',
                                array("objectAction" => "edit", "objectId" => $item->getId())); ?>', '#mautic_role_index');">
                        <i class="fa fa-pencil-square-o"></i>
                    </button>
                    <?php endif; ?>
                    <?php if ($permissions['delete']): ?>
                    <button class="btn btn-danger btn-xs"
                            onclick="Mautic.showConfirmation(
                                '<?php echo $view["translator"]->trans("mautic.user.role.form.confirmdelete",
                                    array("%name%" => $item->getName() . " (" . $item->getId() . ")")
                                ); ?>',
                                '<?php echo $view["translator"]->trans("mautic.core.form.delete"); ?>',
                                'executeAction',
                                [
                                    '<?php echo $view['router']->generate('mautic_role_action',
                                        array("objectAction" => "delete", "objectId" => $item->getId())); ?>',
                                    '#mautic_role_index'
                                ],
                                '<?php echo $view["translator"]->trans("mautic.core.form.cancel"); ?>',
                                '',
                                []
                            );">
                        <i class="fa fa-trash-o"></i>
                    </button>
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
        "baseUrl" =>  $view['router']->generate('mautic_role_index')
    )); ?>
</div>