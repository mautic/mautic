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
                <span class="event-name-slot"><strong>ID:</strong> <?php echo $view->escape($event['extra']['eventId']); ?></span><br/>
                <span class="event-name-slot"><strong>Name:</strong> <?php echo $view->escape($event['extra']['eventName']); ?></span><br/>
                <span class="event-name-slot"><strong>Description:</strong> <?php echo $view->escape($event['extra']['eventDesc']); ?></span><br/>
                <?php if ('' !== $event['extra']['joinUrl']) : ?>
                    <span class="event-desc-slot"><strong>Join URL:</strong> <a href="<?php echo $view->escape($event['extra']['joinUrl']); ?>"><?php echo $view->escape($event['extra']['joinUrl']); ?></a></span>
                <?php endif; ?>
			</p>
		</div>
	</div>
</div>
