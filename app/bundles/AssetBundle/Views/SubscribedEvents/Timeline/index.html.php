<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item = $event['extra']['asset'];

?>

<li class="wrapper asset-download">
	<div class="figure"><span class="fa <?php echo isset($event['icon']) ? $event['icon'] : '' ?>"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<a href="<?php echo $view['router']->generate('mautic_asset_action',
				    array("objectAction" => "view", "objectId" => $item->getId())); ?>"
				   data-toggle="ajax">
				    <?php echo $view->escape($item->getTitle()); ?>
				</a>
			</h3>
            <p class="mb-0"><?php echo $view->escape($view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel']))); ?></p>
	    </div>
	    <?php if (isset($event['extra'])) : ?>
	        <!-- <div class="panel-footer">
	            <p></p>
	        </div> -->
	    <?php endif; ?>
	</div>
</li>
