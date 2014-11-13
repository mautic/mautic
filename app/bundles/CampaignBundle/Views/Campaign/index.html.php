<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'campaign');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.campaigns.header.index'));
?>
<?php if ($permissions['campaign:campaigns:create']): ?>
    <?php $view['slots']->start("actions"); ?>
        <a href="<?php echo $this->container->get('router')->generate('mautic_campaign_action', array("objectAction" => "new")); ?>"
            data-toggle="ajax" class="btn btn-default" data-menu-link="#mautic_campaign_index">
            <i class="fa fa-plus"></i>
            <?php echo $view["translator"]->trans("mautic.campaign.menu.new"); ?>
        </a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<div class="panel panel-default bdr-t-wdh-0">
	<?php echo $view->render('MauticCoreBundle:Helper:listactions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'menuLink'    => 'mautic_campaign_index',
        'langVar'     => 'campaign.campaign',
        'routeBase'   => 'campaign',
        'delete'      => $permissions['campaign:campaigns:delete']
    )); ?>

    <div class="page-list">
		<?php $view['slots']->output('_content'); ?>
	</div>
</div>