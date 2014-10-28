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

<li class="wrapper point-gained">
	<div class="figure"><span class="fa <?php echo isset($icons['points']) ? $icons['points'] : '' ?>"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
				<span class="text-primary"><?php echo $item['eventName']; ?>:</span> <?php echo $item['actionName']; ?>
			</h3>
	        <p class="mb-0">At <?php echo $view['date']->toFullConcat($event['timestamp']); ?>, <?php echo $event['eventLabel']; ?>.</p>
	    </div>
        <div class="panel-footer">
	        <p><strong>Point increment:</strong> <strong class="text-primary"><?php echo $item['delta'] ?></strong></p>
        </div>
	</div>
</li>
