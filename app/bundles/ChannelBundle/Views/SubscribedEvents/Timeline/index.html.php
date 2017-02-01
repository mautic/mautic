<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$extra            = $event['extra'];
$messageSettings  = $extra['messageSettings'];
$eventSettings    = $extra['campaignEventSettings'];
$getChannelOutput = function ($channel) use ($view, $event, $extra, $eventSettings) {
    $log             = $extra['log'];
    $log['metadata'] = $log['metadata'][$channel];

    if (!empty($log['metadata']['dnc'])) {
        switch ($log['metadata']['dnc']) {
            case \Mautic\LeadBundle\Entity\DoNotContact::BOUNCED:
                $msg = 'mautic.lead.event.donotcontact_bounce';
                break;
            case \Mautic\LeadBundle\Entity\DoNotContact::UNSUBSCRIBED:
                $msg = 'mautic.lead.event.donotcontact_unsubscribed';
                break;
            case \Mautic\LeadBundle\Entity\DoNotContact::MANUAL:
                $msg = 'mautic.lead.event.donotcontact_manual';
                break;
        }

        return $view['translator']->trans($msg);
    }

    $template                     = 'MauticCampaignBundle:SubscribedEvents\Timeline:index.html.php';
    $channelEvent                 = $event;
    $channelEvent['extra']['log'] = $log;
    $vars                         = [
        'event' => $channelEvent,
    ];

    // Successful send through this channel
    if (!empty($messageSettings[$channel]['campaignAction'])) {
        $campaignType = $messageSettings[$channel]['campaignAction'];
        if (!empty($eventSettings['action'][$campaignType]['timelineTemplate'])) {
            $template = $eventSettings['action'][$campaignType]['timelineTemplate'];
        }
        if (!empty($eventSettings['action'][$campaignType]['timelineTemplateVars'])) {
            $vars['event']['extra'] = array_merge(
                $vars['event']['extra'],
                $eventSettings['action'][$campaignType]['timelineTemplateVars']
            );
        }
    }

    return $view->render($template, $vars);
};
$counter = count($extra['log']['metadata']);
?>

<?php foreach ($extra['log']['metadata'] as $channel => $results): ?>
    <?php if (isset($messageSettings[$channel])): ?>
    <h4><?php echo $messageSettings[$channel]['label']; ?></h4>
    <?php echo $getChannelOutput($channel); ?>
    <?php --$counter; ?>
    <?php if ($counter > 0): ?>
    <hr />
    <?php endif; ?>
    <?php endif; ?>
<?php endforeach; ?>