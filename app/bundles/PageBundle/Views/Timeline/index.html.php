<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item = $event['extra']['page'];

?>

<li class="wrapper page-hit">
	<div class="figure"><span class="fa <?php echo isset($icons['page']) ? $icons['page'] : '' ?>"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<a href="<?php echo $view['router']->generate('mautic_page_action',
				    array("objectAction" => "view", "objectId" => $item->getId())); ?>"
				   data-toggle="ajax">
				    <?php echo $item->getTitle(); ?>
				</a>
			</h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
	    </div>
	    <?php if (isset($event['extra'])) : ?>
	        <!-- <div class="panel-footer">
	            <p></p>
	        </div> -->
	    <?php endif; ?>
	</div>
</li>
