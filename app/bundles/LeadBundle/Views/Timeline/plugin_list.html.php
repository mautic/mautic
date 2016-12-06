<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$leadId = isset($lead) ? $lead->getId() : null;

$view->extend('MauticLeadBundle:Timeline:plugin_index.html.php');

$baseUrl = isset($lead) ? $view['router']->path(
    'mautic_plugin_timeline_view',
    ['leadId' => $lead->getId(), 'integration' => $integration]
) :
    $view['router']->path('mautic_plugin_timeline_index', ['integration' => $integration]);
?>
<div class="table-responsive">
    <div class="tl-header">
        <?php echo $view['translator']->trans('mautic.lead.timeline.displaying_events', ['%total%' => $events['total']]); ?>
        <?php if (isset($lead)) {
    echo $view['translator']->trans('mautic.lead.timeline.displaying_events_for_contact', ['%contact%' => $lead->getName(), '%id%' => $lead->getId()]);
} ?>
        (<span class="tl-new"><?php echo $newCount; ?></span> <?php echo $view['translator']->trans(
            'mautic.lead.timeline.events_new'
        ); ?>)
    </div>
    <table class="table table-hover table-bordered" id="contact-timeline">
        <thead>
        <tr>
            <th class="timeline-icon">
                <a class="btn btn-sm btn-nospin btn-default" data-activate-details="all" data-toggle="tooltip" title="<?php echo $view['translator']->trans(
                    'mautic.lead.timeline.toggle_all_details'
                ); ?>">
                    <span class="fa fa-fw fa-level-down"></span>
                </a>
            </th>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                'orderBy'    => 'eventLabel',
                'text'       => 'mautic.lead.timeline.event_name',
                'class'      => 'timeline-name',
                'sessionVar' => 'lead.'.$leadId.'.timeline',
                'baseUrl'    => $baseUrl,
                'target'     => '#timeline-table',
            ]);

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                'orderBy'    => 'eventType',
                'text'       => 'mautic.lead.timeline.event_type',
                'class'      => 'visible-md visible-lg timeline-type',
                'sessionVar' => 'lead.'.$leadId.'.timeline',
                'baseUrl'    => $baseUrl,
                'target'     => '#timeline-table',
            ]);

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                'orderBy'    => 'timestamp',
                'text'       => 'mautic.lead.timeline.event_timestamp',
                'class'      => 'visible-md visible-lg timeline-timestamp',
                'sessionVar' => 'lead.'.$leadId.'.timeline',
                'baseUrl'    => $baseUrl,
                'target'     => '#timeline-table',
            ]);
            ?>
        </tr>
        <tbody>
        <?php foreach ($events['events'] as $counter => $event): ?>
            <?php
            $counter += 1; // prevent 0
            $icon       = (isset($event['icon'])) ? $event['icon'] : 'fa-history';
            $eventLabel = (isset($event['eventLabel'])) ? $event['eventLabel'] : $event['eventType'];
            if (is_array($eventLabel)):
                $linkType   = empty($eventLabel['isExternal']) ? 'data-toggle="ajax"' : 'target="_new"';
                $eventLabel = "<a href=\"{$eventLabel['href']}\" $linkType>{$eventLabel['label']}</a>";
            endif;

            $details = '';
            if (isset($event['contentTemplate']) && $view->exists($event['contentTemplate'])):
                $details = trim($view->render($event['contentTemplate'], ['event' => $event]));
            endif;

            $rowStripe = ($counter % 2 === 0) ? ' timeline-row-highlighted' : '';
            ?>
            <tr class="timeline-row<?php echo $rowStripe; ?><?php if (!empty($event['featured'])) {
                echo ' timeline-featured';
            } ?>">
                <td class="timeline-icon">
                    <a data-activate-details="<?php echo $counter; ?>" class="btn btn-sm btn-nospin btn-default<?php if (empty($details)) {
                echo ' disabled';
            } ?>" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.lead.timeline.toggle_details'); ?>" data-target="timeline-details-<?php echo $counter; ?>">
                        <span class="fa fa-fw <?php echo $icon ?>"></span>
                    </a>
                </td>
                <td class="timeline-name"><?php echo $eventLabel; ?></td>
                <td class="timeline-type"><?php if (isset($event['eventType'])) {
                echo $event['eventType'];
            } ?></td>
                <td class="timeline-timestamp"><?php echo $view['date']->toText($event['timestamp'], 'local', 'Y-m-d H:i:s', true); ?></td>
            </tr>
            <?php if (!empty($details)): ?>
                <tr class="timeline-row<?php echo $rowStripe; ?> timeline-details hide" id="timeline-details-<?php echo $counter; ?>">
                    <td colspan="4">
                        <?php echo $details ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div>
<!--/ timeline -->

