<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'leadfield');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.lead.field.header.index'));
?>
<?php $view['slots']->start("actions"); ?>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_leadfield_action', array("objectAction" => "new")); ?>"
        data-toggle="ajax"
        class="btn btn-default"
        data-menu-link="#mautic_lead_index">
        <i class="fa fa-plus"></i> <?php echo $view["translator"]->trans("mautic.lead.field.menu.new"); ?>
    </a>
<?php $view['slots']->stop(); ?>

<div class="panel panel-default bdr-t-wdh-0">
    <?php echo $view->render('MauticCoreBundle:Helper:listactions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'menuLink'    => 'mautic_leadfield_index',
        'langVar'     => 'lead.field',
        'routeBase'   => 'leadfield',
        'delete'      => $permissions['lead:fields:full']
    )); ?>

    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>