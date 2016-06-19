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
        foreach ($campaignSources as $source):
            echo $view->render('MauticCampaignBundle:Source:index.html.php', $source);
        endforeach;

        foreach ($campaignEvents as $event):
            echo $view->render('MauticCampaignBundle:Event:generic.html.php', array('event' => $event, 'campaignId' => $campaignId));
        endforeach;
        ?>
        </div>
    </div>
    <div class="builder-panel" id="CampaignEvents">
        <p>
            <button type="button" class="btn btn-primary btn-close-builder" onclick="Mautic.closeCampaignBuilder();"><?php echo $view['translator']->trans('mautic.core.close.builder'); ?></button>
        </p>

        <div><em><?php echo $view['translator']->trans('mautic.campaign.event.drag.help'); ?></em></div>
        <div class="panel-group mt-sm" id="CampaignEventPanel">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <?php echo $view['translator']->trans('mautic.campaign.leadsource.header'); ?>
                    </h4>
                </div>
                <div class="panel-body">
                    <a id="campaignLeadSource_forms" data-toggle="ajaxmodal" data-target="#CampaignEventModal" class="<?php if (isset($campaignSources['forms'])) echo 'disabled '; ?>list-group-item list-campaign-leadsource" href="<?php echo $view['router']->path('mautic_campaignsource_action', array('objectAction' => 'new', 'objectId' => $campaignId, 'sourceType' => 'forms')); ?>">
                        <div data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.campaign.leadsource.forms.tooltip'); ?>">
                            <span><?php echo $view['translator']->trans('mautic.campaign.leadsource.forms'); ?></span>
                        </div>
                    </a>
                    <a id="campaignLeadSource_lists" data-toggle="ajaxmodal" data-target="#CampaignEventModal" class="<?php if (isset($campaignSources['lists'])) echo 'disabled '; ?>list-group-item list-campaign-leadsource" href="<?php echo $view['router']->path('mautic_campaignsource_action', array('objectAction' => 'new', 'objectId' => $campaignId, 'sourceType' => 'lists')); ?>">
                        <div data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.campaign.leadsource.lists.tooltip'); ?>">
                            <span><?php echo $view['translator']->trans('mautic.campaign.leadsource.lists'); ?></span>
                        </div>
                    </a>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <?php echo $view['translator']->trans('mautic.campaign.event.actions.header'); ?>
                    </h4>
                </div>
                <div class="panel-body">
                    <?php foreach ($eventSettings['action'] as $k => $e): ?>
                        <a id="campaignEvent_<?php echo str_replace('.', '', $k); ?>" data-toggle="ajaxmodal" data-target="#CampaignEventModal" class="list-group-item list-campaign-action" href="<?php echo $view['router']->path('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'action', 'campaignId' => $campaignId)); ?>">
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
                        <?php echo $view['translator']->trans('mautic.campaign.event.decisions.header'); ?>
                    </h4>
                </div>
                <div class="panel-body">
                    <?php foreach ($eventSettings['decision'] as $k => $e): ?>
                        <a id="campaignEvent_<?php echo str_replace('.', '', $k); ?>" data-toggle="ajaxmodal" data-target="#CampaignEventModal" class="list-group-item list-campaign-decision" href="<?php echo $view['router']->path('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'decision', 'campaignId' => $campaignId)); ?>">
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
                        <?php echo $view['translator']->trans('mautic.campaign.event.conditions.header'); ?>
                    </h4>
                </div>
                <div class="panel-body">
                    <?php foreach ($eventSettings['condition'] as $k => $e): ?>
                        <a id="campaignEvent_<?php echo str_replace('.', '', $k); ?>" data-toggle="ajaxmodal" data-target="#CampaignEventModal" class="list-group-item list-campaign-condition" href="<?php echo $view['router']->path('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'condition', 'campaignId' => $campaignId)); ?>">
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
        // Reposition events
        <?php
        if (!empty($canvasSettings)):

        $sourceFound = false;

        foreach ($canvasSettings['nodes'] as $n):

        if (isset($campaignSources[$n['id']]))
            $sourceFound = true;
        ?>
        mQuery('#CampaignEvent_<?php echo $n['id']; ?>').css({
            position: 'absolute',
            left:     '<?php echo $n['positionX']; ?>px',
            top:      '<?php echo $n['positionY']; ?>px'
        });
        <?php endforeach; ?>

        // Recreate jsPlumb connections and labels
        <?php
        $labels = array();

        foreach ($canvasSettings['connections'] as $connection):
            if (isset($labels[$connection['targetId']]) || !isset($campaignEvents[$connection['targetId']]))
                continue;

            $targetEvent = $campaignEvents[$connection['targetId']];
            $labelText   = '';
            if (isset($targetEvent['triggerMode'])):
                if ($targetEvent['triggerMode'] == 'interval'):
                    $labelText = $view['translator']->trans('mautic.campaign.connection.trigger.interval.label', array(
                        '%number%' => $targetEvent['triggerInterval'],
                        '%unit%'   => $view['translator']->transChoice('mautic.campaign.event.intervalunit.' . $targetEvent['triggerIntervalUnit'], $targetEvent['triggerInterval'])
                    ));
                elseif ($targetEvent['triggerMode'] == 'date'):
                    $labelText = $view['translator']->trans('mautic.campaign.connection.trigger.date.label', array(
                        '%full%' => $view['date']->toFull($targetEvent['triggerDate']),
                        '%time%' => $view['date']->toTime($targetEvent['triggerDate']),
                        '%date%' => $view['date']->toShort($targetEvent['triggerDate'])
                    ));
                endif;
            endif;
            $labels[$connection['targetId']] = $labelText;
        ?>

        Mautic.campaignBuilderLabels["CampaignEvent_<?php echo $connection['targetId']; ?>"] = "<?php echo $labelText; ?>";
        <?php endforeach; ?>

        <?php foreach ($canvasSettings['connections'] as $connection): ?>

        Mautic.campaignBuilderInstance.connect({uuids:["<?php echo "CampaignEvent_{$connection['sourceId']}_{$connection['anchors']['source']}"; ?>", "<?php echo "CampaignEvent_{$connection['targetId']}_{$connection['anchors']['target']}"; ?>"]});
        <?php
        endforeach;

        if (!$sourceFound):
        $topOffset = 25;
        foreach ($campaignSources as $type => $source):
        ?>

        mQuery('#CampaignEvent_<?php echo $type; ?>').css({
            position: 'absolute',
            left:     '20px',
            top:      '<?php echo $topOffset; ?>px'
        });
        <?php
        $topOffset += 45;
        endforeach;
        endif;

        endif;
        ?>
    };
</script>
