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

<li class="wrapper point-gained">
	<div class="figure"><span class="fa <?php echo isset($event['icon']) ? $event['icon'] : '' ?>"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
				<span class="text-primary"><?php echo $item['eventName']; ?>:</span> <?php echo $item['actionName']; ?>
			</h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
	    </div>
        <div class="panel-footer">
            <p><?php echo $view['translator']->trans('mautic.point.timeline.event.point.increment', array('%increment%' => $item['delta'])); ?></p>
        </div>
	</div>
</li>
