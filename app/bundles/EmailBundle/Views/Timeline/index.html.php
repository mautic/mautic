<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item = $event['extra']['stats'];

?>

<li class="wrapper email-read">
	<div class="figure"><span class="fa <?php echo isset($icons['email']) ? $icons['email'] : '' ?>"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<a href="<?php echo $view['router']->generate('mautic_email_action',
				    array("objectAction" => "view", "objectId" => $item['email_id'])); ?>"
				   data-toggle="ajax">
				    <?php echo $item['subject']; ?>
				</a>
			</h3>
	        <p class="mb-0">At <?php echo $view['date']->toFullConcat($event['timestamp']); ?>, <?php echo $event['eventLabel']; ?>.</p>
	    </div>
	    <?php if (isset($event['extra'])) : ?>
	        <div class="panel-footer">
	            <p><strong>Email Body:</strong> <?php echo $item['plainText']; ?></p>
	        </div>
	    <?php endif; ?>
	</div>
</li>
