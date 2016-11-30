<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var array $event */
?>

<div class="wrapper citrix-event">
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<?php echo $view->escape($event['eventLabel']); ?>
			</h3>
            <p class="mb-0">
				<?php
                echo $view->escape($view['translator']->trans('mautic.core.timeline.event.time', [
                    '%date%'  => $view['date']->toFullConcat($event['timestamp']),
                    '%event%' => $event['eventLabel'],
                ]));
                ?>
			</p>
	    </div>
		<div class="panel-footer">
			<p>
                <span class="event-name-slot"><?php echo $view->escape($event['extra']['eventName']) ?></span>
				<span class="event-desc-slot"><?php echo $view->escape($event['extra']['eventDesc']) ?></span>
			</p>
		</div>
	</div>
</div>
