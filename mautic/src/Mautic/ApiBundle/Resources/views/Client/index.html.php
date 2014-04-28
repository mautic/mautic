<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view["slots"]->set("headerTitle", $view['translator']->trans('mautic.api.client.header.index'));
?>

<?php if ($permissions['create']): ?>
<?php $view["slots"]->start("actions"); ?>
<li>
    <a href="<?php $this->container->get('router')->generate('mautic_client_action', array("objectAction" => "new")); ?>'"
       data-toggle="ajax">
       <?php echo $view["translator"]->trans("mautic.api.client.menu.new"); ?>
    </a>
</li>
<?php $view["slots"]->stop(); ?>
<?php endif; ?>

<?php
$view['slots']->set('filterUri', $this->container->get('router')->generate('mautic_client_index'));
$view["slots"]->set("filterInput",
    $view->render('MauticCoreBundle:Form:filter.html.php',
        array(
            'filterUri'    => $this->container->get('router')->generate('mautic_client_index'),
            'filterName'   => 'filter-client',
            'filterValue'  => $filterValue,
            'filterTooltip' => 'mautic.api.client.help.searchcommands'
        )
    )
);
?>

<div class="table-responsive body-white padding-sm">
    <table class="table table-hover table-striped table-bordered client-list">
        <thead>
            <tr>
                <?php
                echo $view->render('MauticCoreBundle:Table:tableheader.html.php', array(
                    'entity'  => 'client',
                    'orderBy' => 'c.name',
                    'text'    => 'mautic.api.client.thead.name',
                    'default' => true
                ));
                ?>
                <th class="visible-md visible-lg"><?php echo $view['translator']->trans('mautic.api.client.thead.redirecturis'); ?></th>
                <?php
                echo $view->render('MauticCoreBundle:Table:tableheader.html.php', array(
                    'entity'  => 'client',
                    'orderBy' => 'c.id',
                    'text'    => 'mautic.api.client.thead.id',
                    'class'   => 'visible-md visible-lg'
                ));
                ?>
                <th style="width: 75px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <?php echo $item->getName(true); ?>
                </td>
                <td class="visible-md visible-lg"><?php echo implode("<br />", $item->getRedirectUris()); ?></td>
                <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                <td>
                    <?php if ($permissions['edit']): ?>
                    <a class="btn btn-primary btn-xs"
                       href="<?php echo $view['router']->generate('mautic_client_action',
                            array("objectAction" => "edit", "objectId" => $item->getId())); ?>"
                        data-toggle="ajax"
                        data-menu-link="#mautic_client_index">
                        <i class="fa fa-pencil-square-o"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($permissions['delete']): ?>
                    <a class="btn btn-danger btn-xs" href="javascript: void(0);"
                            onclick="Mautic.showConfirmation('<?php echo $view->escape($view["translator"]->trans("mautic.api.client.form.confirmdelete", array("%name%" => $item->getName() . " (" . $item->getId() . ")")), 'js'); ?>','<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>','executeAction',['<?php echo $view['router']->generate('mautic_client_action', array("objectAction" => "delete", "objectId" => $item->getId())); ?>','#mautic_client_index'],'<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
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
        "baseUrl" =>  $view['router']->generate('mautic_client_index')
    )); ?>
</div>