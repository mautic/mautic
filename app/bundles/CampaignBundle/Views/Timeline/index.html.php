<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item = $event['extra']['log'];

?>

<li class="wrapper campain-event">
	<div class="figure"><span class="icon fa fa-clock-o"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<a href="<?php echo $view['router']->generate('mautic_campaign_action',
				    array("objectAction" => "view", "objectId" => $item['campaign_id'])); ?>"
				   data-toggle="ajax">
				    <?php echo $item['campaignName']; ?>
				</a>
			</h3>
	        <p class="mb-0">At <?php echo $view['date']->toFullConcat($event['timestamp']); ?>, <?php echo $event['eventLabel']; ?>.</p>
	    </div>
        <div class="panel-footer">
            <p>
            	Triggered
				<strong><?php echo $item['eventName']; ?></strong> event
			</p>
			<?php if ($item['campaignDescription']): ?>
			<p>
				<strong>Campaign description: </strong> <?php echo $item['campaignDescription']; ?>
			</p>
			<?php endif; ?>
			<?php if ($item['eventDescription']): ?>
			<p>
				<strong>Event description: </strong> <?php echo $item['eventDescription']; ?>
			</p>
			<?php endif; ?>
        </div>
	</div>
</li>
