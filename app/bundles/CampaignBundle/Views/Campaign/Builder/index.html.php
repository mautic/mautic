<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
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
        <?php
        echo $view->render('MauticCampaignBundle:Campaign\Builder:source_list.html.php',
            [
                'campaignSources' => $campaignSources,
                'campaignId'      => $campaignId,
            ]
        );

        echo $view->render('MauticCampaignBundle:Campaign\Builder:event_list.html.php',
            [
                'eventSettings' => $eventSettings,
                'campaignId'    => $campaignId,
            ]
        );
        ?>

    </div>
</div>
<div class="clearfix"></div>
