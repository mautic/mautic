<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="hide builder campaign-builder">
    <button type="button" class="btn btn-primary btn-close-campaign-builder" onclick="Mautic.closeCampaignBuilder();"><?php echo $view['translator']->trans(
            'mautic.core.close.builder'
        ); ?></button>

    <div class="builder-content">
        <div id="CampaignCanvas">
            <div id="CampaignEvent_newsource<?php if (!empty($campaignSources)) echo "_hide"; ?>" class="text-center list-campaign-source list-campaign-leadsource">
                <div class="campaign-event-content">
                    <div>
                        <span class="campaign-event-name ellipsis">
                            <i class="mr-sm fa fa-users"></i> <?php echo $view['translator']->trans('mautic.campaign.add_new_source'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php
            foreach ($campaignSources as $source):
                echo $view->render('MauticCampaignBundle:Source:index.html.php', $source);
            endforeach;

            foreach ($campaignEvents as $event):
                echo $view->render('MauticCampaignBundle:Event:generic.html.php', ['event' => $event, 'campaignId' => $campaignId]);
            endforeach;
            ?>

            <div class="hide" id="CampaignEventPanel">
                <select id="CampaignEventSelector">
                    <option value=""></option>
                    <optgroup id="SourceGroup" label="<?php echo $view['translator']->trans('mautic.campaign.leadsource.header'); ?>">
                        <?php foreach (['lists', 'forms'] as $option): ?>
                            <option id="campaignLeadSource_<?php echo $option; ?>"
                                    class="list-campaign-leadsource option_campaignLeadSource_<?php echo $option; ?>"
                                    data-href="<?php echo $view['router']->path(
                                        'mautic_campaignsource_action',
                                        ['objectAction' => 'new', 'objectId' => $campaignId, 'sourceType' => $option]
                                    ); ?>"
                                    data-target="#CampaignEventModal"
                                    title="<?php echo $view['translator']->trans('mautic.campaign.leadsource.'.$option.'.tooltip'); ?>"
                                    value="<?php echo $option; ?>"
                                <?php if (!empty($campaignSources[$option])) echo 'disabled'; ?>>
                                <?php echo $view['translator']->trans('mautic.campaign.leadsource.'.$option); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>

                    <?php foreach (['action', 'decision', 'condition'] as $eventGroup): ?>
                        <optgroup id="<?php echo ucfirst($eventGroup); ?>Group" label="<?php echo $view['translator']->trans(
                            'mautic.campaign.event.'.$eventGroup.'s.header'
                        ); ?>">
                            <?php foreach ($eventSettings[$eventGroup] as $k => $e): ?>
                                <option id="campaignEvent_<?php echo str_replace('.', '', $k); ?>"
                                        class="list-campaign-<?php echo $eventGroup; ?> option_campaignEvent_<?php echo str_replace('.', '', $k); ?>"
                                        data-href="<?php echo $view['router']->path(
                                            'mautic_campaignevent_action',
                                            ['objectAction' => 'new', 'type' => $k, 'eventType' => $eventGroup, 'campaignId' => $campaignId]
                                        ); ?>"
                                        data-target="#CampaignEventModal"
                                        title="<?php echo $e['description']; ?>"
                                        value="<?php echo $k; ?>">
                                    <?php echo $e['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>
<!-- dropped coordinates -->
<input type="hidden" value="" id="droppedX"/>
<input type="hidden" value="" id="droppedY"/>
<input type="hidden" value="<?php echo $campaignId; ?>" id="campaignId"/>

<?php echo $view->render(
    'MauticCoreBundle:Helper:modal.html.php',
    [
        'id'            => 'CampaignEventModal',
        'header'        => false,
        'footerButtons' => true,
    ]
);

?>
<script>
    Mautic.campaignBuilderReconnectEndpoints = function () {
        // Reposition events
        <?php
        if (!empty($canvasSettings)):

        $sourceFound = false;

        foreach ($canvasSettings['nodes'] as $n):

        if (isset($campaignSources[$n['id']])) {
            $sourceFound = true;
        }
        ?>
        mQuery('#CampaignEvent_<?php echo $n['id']; ?>').css({
            position: 'absolute',
            left: '<?php echo $n['positionX']; ?>px',
            top: '<?php echo $n['positionY']; ?>px'
        });
        Mautic.campaignBuilderEventPositions['CampaignEvent_<?php echo $n['id']; ?>'] = {
            left: <?php echo $n['positionX']; ?>,
            top: <?php echo $n['positionY']; ?>
        };
        <?php endforeach; ?>

        // Recreate jsPlumb connections and labels
        <?php
        $labels = [];

        foreach ($canvasSettings['connections'] as $connection):
        if (isset($labels[$connection['targetId']]) || !isset($campaignEvents[$connection['targetId']])) {
            continue;
        }

        $targetEvent = $campaignEvents[$connection['targetId']];
        $labelText = '';
        if (isset($targetEvent['triggerMode'])):
            if ($targetEvent['triggerMode'] == 'interval'):
                $labelText = $view['translator']->trans(
                    'mautic.campaign.connection.trigger.interval.label',
                    [
                        '%number%' => $targetEvent['triggerInterval'],
                        '%unit%'   => $view['translator']->transChoice(
                            'mautic.campaign.event.intervalunit.'.$targetEvent['triggerIntervalUnit'],
                            $targetEvent['triggerInterval']
                        ),
                    ]
                );
            elseif ($targetEvent['triggerMode'] == 'date'):
                $labelText = $view['translator']->trans(
                    'mautic.campaign.connection.trigger.date.label',
                    [
                        '%full%' => $view['date']->toFull($targetEvent['triggerDate']),
                        '%time%' => $view['date']->toTime($targetEvent['triggerDate']),
                        '%date%' => $view['date']->toShort($targetEvent['triggerDate']),
                    ]
                );
            endif;
        endif;
        $labels[$connection['targetId']] = $labelText;
        ?>

        Mautic.campaignBuilderLabels["CampaignEvent_<?php echo $connection['targetId']; ?>"] = "<?php echo $labelText; ?>";
        <?php endforeach; ?>

        <?php foreach ($canvasSettings['connections'] as $connection): ?>

        Mautic.campaignBuilderInstance.connect({
            uuids: [
                "<?php echo "CampaignEvent_{$connection['sourceId']}_{$connection['anchors']['source']}"; ?>",
                "<?php echo "CampaignEvent_{$connection['targetId']}_{$connection['anchors']['target']}"; ?>"
            ]
        });
        <?php
        endforeach;

        if (!$sourceFound):
        $topOffset = 25;
        foreach ($campaignSources as $type => $source):
        ?>

        mQuery('#CampaignEvent_<?php echo $type; ?>').css({
            position: 'absolute',
            left: '20px',
            top: '<?php echo $topOffset; ?>px'
        });
        <?php
        $topOffset += 45;
        endforeach;
        endif;

        endif;
        ?>
    };
</script>
