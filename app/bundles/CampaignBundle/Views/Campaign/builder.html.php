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
    <div class="builder-content">
        <div id="CampaignCanvas">
        <?php
        foreach ($campaignEvents as $event):
            echo $view->render('MauticCampaignBundle:Event:generic.html.php', array('event' => $event, 'campaignId' => $campaignId));
        endforeach;
        ?>
        </div>
    </div>
    <div class="builder-panel" id="CampaignEvents">
        <p>
            <button type="button" class="btn btn-primary btn-close-builder" onclick="Mautic.closeCampaignBuilder();"><?php echo $view['translator']->trans('mautic.campaign.campaign.close.builder'); ?></button>
        </p>

        <div><em><?php echo $view['translator']->trans('mautic.campaign.event.drag.help'); ?></em></div>
        <div class="panel-group margin-sm-top" id="CampaignEventPanel">
            <?php /*
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a href="#CampaignEventSystemActions">
                                <?php echo $view['translator']->trans('mautic.campaign.event.systemchanges.header'); ?>
                            </a>
                        </h4>
                    </div>
                    <div class="panel-body">
                        <?php foreach ($eventSettings['systemaction'] as $k => $e): ?>
                            <a id="campaignEvent_<?php echo str_replace('.', '', $k); ?>" data-toggle="ajaxmodal" data-ignore-removemodal="true" data-target="#CampaignEventModal" class="list-group-item list-campaign-systemaction" href="<?php echo $view['router']->generate('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'systemaction')); ?>">
                                <div class="padding-sm" data-toggle="tooltip" title="<?php echo $e['description']; ?>">
                                    <span><?php echo $e['label']; ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                */ ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a href="#CampaignEventOutcomes">
                            <?php echo $view['translator']->trans('mautic.campaign.event.actions.header'); ?>
                        </a>
                    </h4>
                </div>
                <div class="panel-body">
                    <?php foreach ($eventSettings['action'] as $k => $e): ?>
                        <a id="campaignEvent_<?php echo str_replace('.', '', $k); ?>" data-toggle="ajaxmodal" data-ignore-removemodal="true" data-target="#CampaignEventModal" class="list-group-item list-campaign-action" href="<?php echo $view['router']->generate('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'action', 'campaignId' => $campaignId)); ?>">
                            <div data-toggle="tooltip" title="<?php echo $e['description']; ?>">
                                <span><?php echo $e['label']; ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a href="#CampaignEventLeadActions">
                            <?php echo $view['translator']->trans('mautic.campaign.event.decisions.header'); ?>
                        </a>
                    </h4>
                </div>
                <div class="panel-body">
                    <?php foreach ($eventSettings['decision'] as $k => $e): ?>
                        <a id="campaignEvent_<?php echo str_replace('.', '', $k); ?>" data-toggle="ajaxmodal" data-ignore-removemodal="true" data-target="#CampaignEventModal" class="list-group-item list-campaign-decision" href="<?php echo $view['router']->generate('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'decision', 'campaignId' => $campaignId)); ?>">
                            <div data-toggle="tooltip" title="<?php echo $e['description']; ?>">
                                <span><?php echo $e['label']; ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- dropped coordinates -->
<input type="hidden" value="" id="droppedX" />
<input type="hidden" value="" id="droppedY" />
<input type="hidden" value="<?php echo $campaignId; ?>" id="campaignId" />

<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'            => 'CampaignEventModal',
    'header'        => false,
    'footerButtons' => true
));
?>
<script>
    Mautic.campaignBuilderReconnectEndpoints = function() {
        <?php //recreate jsPlumb connections
        foreach ($campaignEvents as $e):
            if (isset($e['canvasSettings']['endpoints'])):
                foreach ($e['canvasSettings']['endpoints'] as $sourceEndpoint => $targets):
                    foreach ($targets as $targetId => $targetEndpoint):
                        $useId = (strpos($targetId, 'new') === 0 && isset($tempEventIds[$targetId])) ? $tempEventIds[$targetId] : $targetId;
                        $source = "CampaignEvent_{$e['id']}";
                        $target = "CampaignEvent_{$useId}";
                        $label  = '';
                        if (isset($campaignEvents[$useId])):
                            $targetEvent = $campaignEvents[$useId];
                            $labelText   = '';
                            if (isset($targetEvent['triggerMode'])):
                                if ($targetEvent['triggerMode'] == 'interval'):
                                    $labelText = $view['translator']->trans('mautic.campaign.connection.trigger.interval.label', array(
                                        '%number%' => $targetEvent['triggerInterval'],
                                        '%unit%'   => $view['translator']->transChoice('mautic.campaign.event.intervalunit.' . $targetEvent['triggerIntervalUnit'], $targetEvent['triggerInterval'])
                                    ));
                                elseif ($targetEvent['triggerMode'] == 'date'):
                                    $labelText = $view['translator']->trans('mautic.campaign.connection.trigger.date.label', array(
                                        '%full%' => $view['date']->toFull($event['triggerDate']),
                                        '%time%' => $view['date']->toTime($targetEvent['triggerDate']),
                                        '%date%' => $view['date']->toShort($targetEvent['triggerDate'])
                                    ));
                                endif;
                            endif;
                            if (!empty($labelText)):
                                $label = " ep.addOverlay([\"Label\", {label: \"$labelText\", location: 0.65, id: \"{$source}_{$target}_connectionLabel\", cssClass: \"_jsPlumb_label\"}]); ";
                            endif;
                        endif;
                        echo "if (mQuery(\"#{$source}\").length && mQuery(\"#{$target}\").length) { var ep = Mautic.campaignBuilderInstance.connect({uuids:[\"{$source}_{$sourceEndpoint}\", \"{$target}_{$targetEndpoint}\"]});$label}\n\n";
                    endforeach;
                endforeach;
            endif;
        endforeach;
        ?>
    };
</script>