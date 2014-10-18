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
	<div class="figure"><span class="icon fa fa-calculator"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<a href="<?php echo $view['router']->generate('mautic_point_action',
				    array("objectAction" => "view", "objectId" => $item->getId())); ?>"
				   data-toggle="ajax">
				    <?php echo $item->getName(); ?>
				</a>
			</h3>
	        <p class="mb-0">At <?php echo $view['date']->toFullConcat($event['timestamp']); ?>, <?php echo $event['eventLabel']; ?>.</p>
	    </div>
        <div class="panel-footer">
	        <p><strong>Point description:</strong> <?php echo $item->getDescription() ?></p>
	        <p><strong>Point type:</strong> <?php echo $item->getType() ?></p>
        </div>
	</div>
</li>
