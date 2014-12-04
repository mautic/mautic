<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item = $event['extra']['page'];
$timeOnPage = $view['translator']->trans('mautic.core.unknown');
if ($event['extra']['hit']['dateLeft']) {
	$timeOnPage = ($event['extra']['hit']['dateLeft']->getTimestamp() - $event['extra']['hit']['dateHit']->getTimestamp());

	// format the time
	if ($timeOnPage > 60) {
		$sec = $timeOnPage % 60;
		$min = floor($timeOnPage / 60);
		$timeOnPage = $min . 'm ' . $sec . 's';
	} else {
		$timeOnPage .= 's';
	}
}
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
	        <div class="panel-footer">
	            <dl class="dl-horizontal">
					<dt><?php echo $view['translator']->trans('mautic.page.time.on.page'); ?>:</dt>
					<dd><?php echo $timeOnPage; ?></dd>
					<dt><?php echo $view['translator']->trans('mautic.page.referrer'); ?>:</dt>
					<dd><?php echo $event['extra']['hit']['referer'] ? $view['assets']->makeLinks($event['extra']['hit']['referer']) : $view['translator']->trans('mautic.core.unknown'); ?></dd>
				</dl>
	        </div>
	    <?php endif; ?>
	</div>
</li>
