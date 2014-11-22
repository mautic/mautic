<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'lead');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.lead.lead.header.index'));
?>

<?php if ($permissions['lead:leads:create']): ?>
    <?php $view['slots']->start("actions"); ?>
        <a id="new-lead" href="<?php echo $this->container->get('router')->generate('mautic_lead_action', array("objectAction" => "new")); ?>" data-toggle="ajax" class="btn btn-default" data-menu-link="#mautic_lead_index">
            <i class="fa fa-plus"></i> <?php echo $view["translator"]->trans("mautic.lead.lead.menu.new"); ?>
        </a>
        <button class="btn btn-default" data-toggle="modal" data-target="#lead-quick-add">
            <i class="fa fa-plus"></i> <?php echo $view["translator"]->trans("mautic.lead.lead.menu.quickadd"); ?>
        </button>
        <div class="btn-group">
          <a id="table-view" href="<?php echo $view['router']->generate('mautic_lead_index', array('page' => $page, 'view' => 'list')); ?>" data-toggle="ajax" class="btn btn-default"><i class="fa fa-fw fa-table"></i></a>
          <a id="card-view" href="<?php echo $view['router']->generate('mautic_lead_index', array('page' => $page, 'view' => 'grid')); ?>" data-toggle="ajax" class="btn btn-default"><i class="fa fa-fw fa-th-large"></i></a>
        </div>
    <?php
    echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
        'id'     => 'lead-quick-add',
        'header' => $view['translator']->trans('mautic.lead.lead.header.quick.add'),
        'body'   => $view->render('MauticLeadBundle:Lead:quickadd.html.php', array('form' => $quickForm)),
        'size'   => 'sm',
        'footer' => '<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times text-danger "></i> ' . $view["translator"]->trans("mautic.core.form.cancel") . '</button>'
                    . '<button id="save-quick-add" type="button" class="btn btn-default" onclick="mQuery(\'form[name=lead]\').submit()"><i class="fa fa-save"></i> ' . $view["translator"]->trans("mautic.core.form.save") . '</button>'
    ));
    ?>
    <?php $view['slots']->stop(); ?>

<?php endif; ?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php //TODO - Restore these buttons to the listactions when custom content is supported
    /*<div class="btn-group">
        <button type="button" class="btn btn-default"><i class="fa fa-upload"></i></button>
        <button type="button" class="btn btn-default"><i class="fa fa-archive"></i></button>
    </div>*/ ?>
    <?php echo $view->render('MauticCoreBundle:Helper:listactions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'menuLink'    => 'mautic_lead_index',
        'langVar'     => 'lead.lead',
        'routeBase'   => 'lead',
        'delete'      => $permissions['lead:leads:deleteown'] || $permissions['lead:leads:deleteother']
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
