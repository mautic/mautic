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

$buttons = $preButtons = array();
if ($permissions['lead:leads:create']) {
    $preButtons[] = array(
        'attr'      => array(
            'class'       => 'btn btn-default btn-nospin',
            'data-toggle' => 'modal',
            'data-target' => '#lead-quick-add'
        ),
        'iconClass' => 'fa fa-bolt',
        'btnText'   => 'mautic.lead.lead.menu.quickadd'
    );
}

$extraHtml = <<<button
<div class="btn-group ml-5">
    <a id="table-view" href="{$view['router']->generate('mautic_lead_index', array('page' => $page, 'view' => 'list'))}" data-toggle="ajax" class="btn btn-default"><i class="fa fa-fw fa-table"></i></a>
    <a id="card-view" href="{$view['router']->generate('mautic_lead_index', array('page' => $page, 'view' => 'grid'))}" data-toggle="ajax" class="btn btn-default"><i class="fa fa-fw fa-th-large"></i></a>
</div>
button;

$extraHtml .=  $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'lead-quick-add',
    'header' => $view['translator']->trans('mautic.lead.lead.header.quick.add'),
    'body'   => $view->render('MauticLeadBundle:Lead:quickadd.html.php', array('form' => $quickForm)),
    'size'   => 'sm',
    'footer' =>
        '<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times text-danger "></i> ' . $view["translator"]->trans("mautic.core.form.cancel") . '</button>' .
        '<button id="save-quick-add" type="button" class="btn btn-default" onclick="mQuery(\'form[name=lead]\').submit()"><i class="fa fa-save"></i> ' . $view["translator"]->trans("mautic.core.form.save") . '</button>'
));

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'templateButtons' => array(
        'new' => $permissions['lead:leads:create']
    ),
    'routeBase' => 'lead',
    'langVar'   => 'lead.lead',
    'preCustomButtons' => $preButtons,
    'customButtons'    => $buttons,
    'extraHtml'        => $extraHtml
)));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php //TODO - Restore these buttons to the listactions when custom content is supported
    /*<div class="btn-group">
        <button type="button" class="btn btn-default"><i class="fa fa-upload"></i></button>
        <button type="button" class="btn btn-default"><i class="fa fa-archive"></i></button>
    </div>*/ ?>
    <?php echo $view->render('MauticCoreBundle:Helper:bulk_actions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'langVar'     => 'lead.lead',
        'routeBase'   => 'lead',
        'templateButtons' => array(
            'delete' => $permissions['lead:leads:deleteown'] || $permissions['lead:leads:deleteother']
        )
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
