<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
	    </div>
	    <?php if (isset($event['extra'])) : ?>
	        <div class="panel-footer">
	            <p>
	            <?php if (empty($item['dateRead'])) : ?>
	            	<?php echo $view['translator']->trans('mautic.email.timeline.event.not.read'); ?>
	            <?php else : ?>
	            	<?php echo $view['translator']->trans('mautic.email.timeline.event.read', array('%date%' => $view['date']->toFull($item['dateRead']), '%interval%' => $view['date']->formatRange($item['timeToRead']))); ?>
	            <?php endif; ?>
	            <?php if (!empty($item['viewedInBrowser'])) : ?>
	            	<?php echo $view['translator']->trans('mautic.email.timeline.event.viewed.in.browser'); ?>
	            <?php endif; ?>
	            <?php if (!empty($item['retryCount'])) : ?>
	            	<?php echo $view['translator']->transChoice('mautic.email.timeline.event.retried', $item['retryCount'], array('%count%' => $item['retryCount'])); ?>
	            <?php endif; ?>
	            <?php if (!empty($item['list_name'])) : ?>
	            	<?php echo $view['translator']->trans('mautic.email.timeline.event.list', array('%list%' => $item['list_name'])); ?>
	            <?php endif; ?>
	            </p>
	        </div>
	    <?php endif; ?>
	</div>
</li>
