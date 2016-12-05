<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="hide builder campaign-builder">
    <button type="button" class="btn btn-primary btn-close-campaign-builder" onclick="Mautic.closeCampaignBuilder();">
        <?php echo $view['translator']->trans('mautic.core.close.builder'); ?>
    </button>

    <div class="builder-content">
        <div id="CampaignCanvas">
            <div id="CampaignEvent_newsource<?php if (!empty($campaignSources)) {
    echo '_hide';
} ?>" class="text-center list-campaign-source list-campaign-leadsource">
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
                <div id="CampaignEventPanelGroups">
                    <div class="row">
                        <div class="mr-0 ml-0 pl-xs pr-xs campaign-group-container col-md-4" id="DecisionGroupSelector">
                            <div class="panel panel-success mb-0">
                                <div class="panel-heading">
                                    <div class="col-xs-8 col-sm-10 np">
                                        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.campaign.event.decision.header'); ?></h3>
                                    </div>
                                    <div class="col-xs-4 col-sm-2 pl-0 pr-0 pt-10 pb-10 text-right">
                                        <i class="hidden-xs fa fa-random fa-lg"></i>
                                        <button class="visible-xs pull-right btn btn-sm btn-default btn-nospin text-success" data-type="Decision">
                                            <?php echo $view['translator']->trans('mautic.core.select'); ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <?php echo $view['translator']->trans('mautic.campaign.event.decision.descr'); ?>
                                </div>
                                <div class="hidden-xs panel-footer text-center">
                                    <button class="btn btn-lg btn-default btn-nospin text-success" data-type="Decision">
                                        <?php echo $view['translator']->trans('mautic.core.select'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mr-0 ml-0 pl-xs pr-xs campaign-group-container col-md-4 " id="ActionGroupSelector">
                            <div class="panel panel-primary mb-0">
                                <div class="panel-heading">
                                    <div class="col-xs-8 col-sm-10 np">
                                        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.campaign.event.action.header'); ?></h3>
                                    </div>
                                    <div class="col-xs-4 col-sm-2 pl-0 pr-0 pt-10 pb-10 text-right">
                                        <i class="hidden-xs fa fa-bullseye fa-lg"></i>
                                        <button class="visible-xs pull-right btn btn-sm btn-default btn-nospin text-primary" data-type="Action">
                                            <?php echo $view['translator']->trans('mautic.core.select'); ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <?php echo $view['translator']->trans('mautic.campaign.event.action.descr'); ?>
                               </div>
                                <div class="hidden-xs panel-footer text-center">
                                    <button class="btn btn-lg btn-default btn-nospin text-primary" data-type="Action">
                                        <?php echo $view['translator']->trans('mautic.core.select'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mr-0 ml-0 pl-xs pr-xs campaign-group-container col-md-4" id="ConditionGroupSelector">
                            <div class="panel panel-danger mb-0">
                                <div class="panel-heading">
                                    <div class="col-xs-8 col-sm-10 np">
                                        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.campaign.event.condition.header'); ?></h3>
                                    </div>
                                    <div class="col-xs-4 col-sm-2 pl-0 pr-0 pt-10 pb-10 text-right">
                                        <i class="hidden-xs fa fa-filter fa-lg"></i>
                                        <button class="visible-xs pull-right btn btn-sm btn-default btn-nospin text-danger" data-type="Condition">
                                            <?php echo $view['translator']->trans('mautic.core.select'); ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="panel-body"><?php echo $view['translator']->trans('mautic.campaign.event.condition.descr'); ?></div>
                                <div class="hidden-xs panel-footer text-center">
                                    <button class="btn btn-lg btn-default btn-nospin text-danger" data-type="Condition">
                                        <?php echo $view['translator']->trans('mautic.core.select'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="CampaignEventPanelLists" class="hide">
                    <div id="SourceGroupList" class="hide">
                        <h4 class="mb-xs">
                            <span><?php echo $view['translator']->trans('mautic.campaign.leadsource.header'); ?></span>
                        </h4>
                        <select id="SourceList" class="campaign-event-selector">
                            <option value=""></option>
                            <?php foreach (['lists', 'forms'] as $option): ?>

                            <option id="campaignLeadSource_<?php echo $option; ?>"
                                    class="option_campaignLeadSource_<?php echo $option; ?>"
                                    data-href="<?php echo $view['router']->path(
                                        'mautic_campaignsource_action',
                                        ['objectAction' => 'new', 'objectId' => $campaignId, 'sourceType' => $option]
                                    ); ?>"
                                    data-target="#CampaignEventModal"
                                    title="<?php echo $view->escape($view['translator']->trans('mautic.campaign.leadsource.'.$option.'.tooltip')); ?>"
                                    value="<?php echo $option; ?>"
                                <?php if (!empty($campaignSources[$option])) {
                                        echo 'disabled';
                                    } ?>>
                                <span><?php echo $view['translator']->trans('mautic.campaign.leadsource.'.$option); ?></span>
                            </option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                    <?php foreach (['action' => 'primary', 'decision' => 'success', 'condition' => 'danger'] as $eventGroup => $color): ?>
                    <div id="<?php echo ucfirst($eventGroup); ?>GroupList" class="hide">
                        <h4 class="mb-xs">
                            <span><?php echo $view['translator']->trans('mautic.campaign.event.'.$eventGroup.'s.header'); ?></span>
                            <button class="pull-right btn btn-xs btn-nospin btn-<?php echo $color; ?> ">
                                <i class="fa fa-fw fa-level-up"></i>
                            </button>
                        </h4>
                        <select id="<?php echo ucfirst($eventGroup); ?>List" class="campaign-event-selector">
                            <option value=""></option>
                            <?php foreach ($eventSettings[$eventGroup] as $k => $e): ?>

                            <option id="campaignEvent_<?php echo str_replace('.', '', $k); ?>"
                                    class="option_campaignEvent_<?php echo str_replace('.', '', $k); ?>"
                                    data-href="<?php echo $view['router']->path(
                                        'mautic_campaignevent_action',
                                        ['objectAction' => 'new', 'type' => $k, 'eventType' => $eventGroup, 'campaignId' => $campaignId, 'anchor' => '']
                                    ); ?>"
                                    data-target="#CampaignEventModal"
                                    title="<?php echo $view->escape($e['description']); ?>"
                                    value="<?php echo $k; ?>">
                                <span><?php echo $e['label']; ?></span>
                            </option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="clearfix"></div>
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
        foreach ($canvasSettings['connections'] as $connection):
        if (isset($campaignEvents[$connection['targetId']])):
            $targetEvent = $campaignEvents[$connection['targetId']];
        elseif (isset($campaignSources[$connection['targetId']])):
            $targetEvent = $campaignSources[$connection['targetId']];
        else:
            continue;
        endif;

        $labelText = '';
        if (isset($targetEvent['triggerMode'])):
            if ($targetEvent['triggerMode'] == 'interval'):
                $labelText = $view['translator']->trans(
                    'mautic.campaign.connection.trigger.interval.label'.($targetEvent['decisionPath'] == 'no' ? '_inaction' : ''),
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
                    'mautic.campaign.connection.trigger.date.label'.($targetEvent['decisionPath'] == 'no' ? '_inaction' : ''),
                    [
                        '%full%' => $view['date']->toFull($targetEvent['triggerDate']),
                        '%time%' => $view['date']->toTime($targetEvent['triggerDate']),
                        '%date%' => $view['date']->toShort($targetEvent['triggerDate']),
                    ]
                );
            endif;
        endif;
        ?>
        <?php if (!empty($labelText)): ?>

        Mautic.campaignBuilderLabels["CampaignEvent_<?php echo $connection['targetId']; ?>"] = "<?php echo $labelText; ?>";
        <?php endif; ?>

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

    Mautic.campaignBuilderConnectionRestrictions = {
        <?php foreach ($eventSettings['connectionResrictions'] as $group => $groupRestrictions): ?>

        '<?php echo $group; ?>': {

            <?php
            $restrictions = [];
            foreach ($groupRestrictions as $event => $allowed):
                $allowedString  = implode("', '", $allowed);
                $restrictions[] = "'$event': ['$allowedString']";
            endforeach;
            ?>

            <?php echo implode(", \n", $restrictions); ?>

        },
        <?php endforeach; ?>

        'anchors': {

            <?php foreach ($eventSettings['anchorRestrictions'] as $group => $groupRestrictions): ?>
            '<?php echo $group; ?>': {

                <?php
                $restrictions = [];
                foreach ($groupRestrictions as $event => $disallow):
                    $allowedString  = implode("', '", $disallow);
                    $restrictions[] = "'$event': ['$allowedString']";
                endforeach;
                ?>

                <?php echo implode(", \n", $restrictions); ?>

            },

            <?php endforeach; ?>

        }
    };
</script>