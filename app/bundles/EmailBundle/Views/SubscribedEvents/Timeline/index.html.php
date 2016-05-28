<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item    = $event['extra']['stat'];
$subject = $view['translator']->trans('mautic.email.timeline.event.custom_email');

if (!empty($item['storedSubject'])) {
	$subject .= ': '.$item['storedSubject'];
} elseif (!empty($item['subject'])) {
	/**
	 * @deprecated 1.2.3 - to be removed in 2.0
	 */
	$subject = ': '.$item['subject'];
}
?>

<li class="wrapper email-read">
	<div class="figure"><span class="fa <?php echo isset($event['icon']) ? $event['icon'] : '' ?>"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<a target="_new" href="<?php echo $view['router']->generate('mautic_email_webview', array("idHash" => $item['idHash'])); ?>">
				    <?php echo $subject; ?>
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
	            	<?php echo $view['translator']->trans('mautic.email.timeline.event.' . $event['extra']['type'], array('%date%' => $view['date']->toFull($item['dateRead']), '%interval%' => $view['date']->formatRange($item['timeToRead']), '%sent%' => $view['date']->toFull($item['dateSent']))); ?>
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
		        <?php if (isset($item['openDetails']['bounces'])): ?>
		        <?php unset($item['openDetails']['bounces']); ?>
		        <?php endif; ?>

		        <?php if (!empty($item['openDetails'])): ?>
		        <div class="small">
		        <?php foreach ($item['openDetails'] as $detail): ?>
                    <hr />
					<strong><?php echo $view['date']->toText($detail['datetime'], 'UTC'); ?></strong><br /><?php echo $detail['useragent']; ?>
			        <?php endforeach; ?>
		        </div>
		        <?php endif; ?>
	        </div>
	    <?php endif; ?>
	</div>
</li>
