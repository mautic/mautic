<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$item = $event['extra']['log'];
?>

<?php if ($item['isScheduled']): ?>
    <p class="mb-0 text-danger"><?php echo $view['translator']->trans('mautic.core.timeline.event.scheduled.time', ['%date%' => $view['date']->toFullConcat($item['triggerDate']), '%event%' => $event['eventLabel']]); ?></p>
<?php endif; ?>

<?php if (!empty($item['metadata']['timeline'])): ?>
    <p><?php echo $item['metadata']['timeline']; ?></p>
<?php endif; ?>

<?php if ($item['campaign_description']): ?>
    <p><?php echo $view['translator']->trans('mautic.campaign.campaign.description', ['%description%' => $item['campaign_description']]); ?></p>
<?php endif; ?>
<?php if ($item['event_description']): ?>
    <p><?php echo $view['translator']->trans('mautic.campaign.campaign.description', ['%description%' => $item['event_description']]); ?></p>
<?php endif; ?>
