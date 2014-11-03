<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="hide campaign-builder">
    <div class="campaign-builder-content">
       <?php /* <input type="hidden" id="pageBuilderUrl" value="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'builder', 'objectId' => $activePage->getSessionId())); ?>" /> */ ?>
        <div id="CampaignCanvasContainer">
            <div id="CampaignCanvas">
                <?php
                foreach ($campaignEvents as $event):
                    echo $view->render('MauticCampaignBundle:Event:generic.html.php', array('event' => $event));
                endforeach;
                ?>
            </div>
            <div class="campaign-builder-panel" id="CampaignEvents">
                <p>
                    <button class="btn btn-primary btn-close-builder" onclick="Mautic.closeCampaignBuilder();"><?php echo $view['translator']->trans('mautic.campaign.campaign.close.builder'); ?></button>
                </p>

                <div><em><?php echo $view['translator']->trans('mautic.campaign.event.drag.help'); ?></em></div>
                <div class="panel-group margin-sm-top" id="CampaignEventPanel">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" data-parent="#CampaignEventPanel" href="#CampaignEventLeadActions">
                                    <?php echo $view['translator']->trans('mautic.campaign.event.decisions.header'); ?>
                                </a>
                            </h4>
                        </div>
                        <div id="CampaignEventLeadActions" class="panel-collapse collapse in">
                            <div class="panel-body">
                                <div class="campaign-event-group-body list-group">
                                <?php foreach ($eventSettings['decision'] as $k => $e): ?>
                                    <a id="campaignEvent_<?php echo str_replace('.', '', $k); ?>" data-toggle="ajaxmodal" data-target="#CampaignEventModal" class="list-group-item list-campaign-decision" href="<?php echo $view['router']->generate('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'decision')); ?>">
                                        <div class="padding-sm" data-toggle="tooltip" title="<?php echo $e['description']; ?>">
                                            <span><?php echo $e['label']; ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php /*
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" data-parent="#CampaignEventPanel" href="#CampaignEventSystemActions">
                                    <?php echo $view['translator']->trans('mautic.campaign.event.systemchanges.header'); ?>
                                </a>
                            </h4>
                        </div>
                        <div id="CampaignEventSystemActions" class="panel-collapse collapse">
                            <div class="panel-body">
                                <div class="campaign-event-group-body list-group">
                                    <?php foreach ($eventSettings['systemaction'] as $k => $e): ?>
                                        <a id="campaignEvent_<?php echo str_replace('.', '', $k); ?>" data-toggle="ajaxmodal" data-target="#CampaignEventModal" class="list-group-item list-campaign-systemaction" href="<?php echo $view['router']->generate('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'systemaction')); ?>">
                                            <div class="padding-sm" data-toggle="tooltip" title="<?php echo $e['description']; ?>">
                                                <span><?php echo $e['label']; ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    */ ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" data-parent="#CampaignEventPanel" href="#CampaignEventOutcomes">
                                    <?php echo $view['translator']->trans('mautic.campaign.event.actions.header'); ?>
                                </a>
                            </h4>
                        </div>
                        <div id="CampaignEventOutcomes" class="panel-collapse collapse">
                            <div class="panel-body">
                                <?php foreach ($eventSettings['action'] as $k => $e): ?>
                                <a id="campaignEvent_<?php echo str_replace('.', '', $k); ?>" data-toggle="ajaxmodal" data-target="#CampaignEventModal" class="list-group-item list-campaign-action" href="<?php echo $view['router']->generate('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'action')); ?>">
                                    <div class="padding-sm" data-toggle="tooltip" title="<?php echo $e['description']; ?>">
                                        <span><?php echo $e['label']; ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- dropped coordinates -->
<input type="hidden" value="" id="droppedX" />
<input type="hidden" value="" id="droppedY" />

<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'CampaignEventModal',
    'header' => false
));
?>
<script>
    Mautic.campaignBuilderReconnectEndpoints = function() {
        Mautic.campaignBuilderIgnoreUpdateConnectionCallback = true;
        <?php //recreate jsPlumb connections
        foreach ($campaignEvents as $e):
            if (isset($e['canvasSettings']['endpoints'])):
                foreach ($e['canvasSettings']['endpoints'] as $sourceEndpoint => $targets):
                    foreach ($targets as $targetId => $targetEndpoint):
                        $useId = (strpos($targetId, 'new') === 0 && isset($tempEventIds[$targetId])) ? $tempEventIds[$targetId] : $targetId;
                        $source = "CampaignEvent_{$e['id']}";
                        $target = "CampaignEvent_{$useId}";
                        echo "if (mQuery(\"#{$source}\").length && mQuery(\"#{$target}\").length) { Mautic.campaignBuilderInstance.connect({uuids:[\"{$source}_{$sourceEndpoint}\", \"{$target}_{$targetEndpoint}\"]}); }\n";
                    endforeach;
                endforeach;
            endif;
        endforeach;
        ?>
        Mautic.campaignBuilderIgnoreUpdateConnectionCallback = false;
    };
</script>