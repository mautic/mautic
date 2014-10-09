<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$containerClass = (!empty($deleted)) ? ' bg-danger' : '';

if (!isset($childrenHtml)) {
    $childrenHtml = '';
}

if ($event instanceof \Mautic\CampaignBundle\Entity\Event) {
    $event = $event->convertToArray();
}
?>

<li class="campaign-event-row campaign-event-<?php echo $event['eventType']; ?><?php echo $containerClass; ?>" id="CampaignEvent_<?php echo $id; ?>">
    <div class="campaign-event-details">
        <?php
        if (!empty($inForm)):
            echo $view->render('MauticCampaignBundle:CampaignBuilder:actions.html.php', array(
                'deleted'  => (!empty($deleted)) ? $deleted : false,
                'id'       => $id,
                'route'    => 'mautic_campaignevent_action',
                'level'    => $level
            ));
        endif;
        if ($event['eventType'] == 'action'):
        ?>
        <div class="pull-right campaign-event-timeframe">
            <?php if ($event['triggerMode'] == 'immediate'): ?>
            <i class="fa fa-fw fa-clock-o"></i><?php echo $view['translator']->trans('mautic.campaign.event.inline.triggerimmediately'); ?>
            <?php elseif ($event['triggerMode'] == 'date'): ?>
            <i class="fa fa-fw fa-clock-o"></i><?php echo $view['date']->toFull($event['triggerDate']); ?>
            <?php else: ?>
            <i class="fa fa-fw fa-clock-o"></i>
                <?php echo $view['translator']->trans('mautic.campaign.event.inline.triggerinterval', array(
                    '%interval%' => $event['triggerInterval'],
                    '%unit%'     => $view['translator']->trans('mautic.campaign.event.intervalunit.' . $event['triggerIntervalUnit'])
                ));
            endif;
            ?>
        </div>
        <?php endif; ?>
        <div class="pull-left">
            <?php $icon = ($event['eventType'] == 'trigger') ? 'fa-bullseye text-danger' : 'fa-rocket text-success'; ?>
            <span class="campaign-event-label" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.campaign.event.'.$event['eventType']); ?>"><i class="fa mr5 <?php echo $icon; ?>"></i><?php echo $event['name']; ?></span>
            <?php if (!empty($event['description'])): ?>
            <span class="campaign-event-descr"><?php echo $event['description']; ?></span>
            <?php endif; ?>
        </div>
        <div class="clearfix"></div>
    </div>
    <?php echo $childrenHtml; ?>
</li>