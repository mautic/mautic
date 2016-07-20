<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


?>

<?php if (isset($event['extra'])) : ?>
<p>
<?php if (empty($item['dateRead'])) : ?>
    <?php echo $view['translator']->trans('mautic.email.timeline.event.not.read'); ?>
<?php else : ?>
    <?php echo $view['translator']->trans(
'mautic.email.timeline.event.'.$event['extra']['type'],
[
'%date%'     => $view['date']->toFull($item['dateRead']),
'%interval%' => $view['date']->formatRange($item['timeToRead']),
'%sent%'     => $view['date']->toFull($item['dateSent']),
]
); ?>
<?php endif; ?>
<?php if (!empty($item['viewedInBrowser'])) : ?>
    <?php echo $view['translator']->trans('mautic.email.timeline.event.viewed.in.browser'); ?>
<?php endif; ?>
<?php if (!empty($item['retryCount'])) : ?>
    <?php echo $view['translator']->transChoice(
'mautic.email.timeline.event.retried',
$item['retryCount'],
['%count%' => $item['retryCount']]
); ?>
<?php endif; ?>
<?php if (!empty($item['list_name'])) : ?>
    <?php echo $view['translator']->trans('mautic.email.timeline.event.list', ['%list%' => $item['list_name']]); ?>
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
