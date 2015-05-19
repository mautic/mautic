<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item = $event['extra']['log'];

?>

<li class="wrapper campaign-event">
	<div class="figure"><span class="fa <?php echo isset($icons['campaigns']) ? $icons['campaigns'] : '' ?>"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<a href="<?php echo $view['router']->generate('mautic_campaign_action',
				    array("objectAction" => "view", "objectId" => $item['campaign_id'])); ?>"
				   data-toggle="ajax">
				    <?php echo $item['campaign_name']; ?>
				</a>
			</h3>
	        <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
	    </div>
        <div class="panel-footer">
            <?php if (!empty($item['metadata']['timeline'])): ?>
            <p><?php echo $item['metadata']['timeline']; ?></p>
            <?php endif; ?>
            <p><?php echo $view['translator']->trans('mautic.campaign.user.event.triggered', array('%event%' => $item['event_name'])); ?></p>
			<?php if ($item['campaign_description']): ?>
			<p><?php echo $view['translator']->trans('mautic.campaign.campaign.description', array('%description%' => $item['campaign_description'])); ?></p>
			<?php endif; ?>
			<?php if ($item['event_description']): ?>
			<p><?php echo $view['translator']->trans('mautic.campaign.campaign.description', array('%description%' => $item['event_description'])); ?></p>
			<?php endif; ?>
        </div>
	</div>
</li>
