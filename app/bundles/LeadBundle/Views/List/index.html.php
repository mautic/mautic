<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'leadlist');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.lead.list.header.index'));
?>
<?php $view['slots']->start("actions"); ?>
	<a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
	        'mautic_leadlist_action', array("objectAction" => "new")); ?>" data-toggle="ajax">
	        <i class="fa fa-plus"></i>
	        <?php echo $view["translator"]->trans("mautic.lead.list.menu.new"); ?>
	</a>
<?php $view['slots']->stop(); ?>

<div class="panel panel-default bdr-t-wdh-0">
    <?php echo $view->render('MauticCoreBundle:Helper:listactions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'menuLink'    => 'mautic_leadlist_index',
        'langVar'     => 'lead.list',
        'routeBase'   => 'leadlist',
        'delete'      => $permissions['lead:lists:deleteother']
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
