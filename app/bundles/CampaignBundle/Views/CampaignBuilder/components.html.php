<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticCampaignBundle:CampaignBuilder:index.html.php');
}
?>

<div id="campaignEventList">
    <div class="panel-group" id="campaignEventPanel">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#campaignEventPanel" href="#campaignEventTriggers">
                        <?php echo $view['translator']->trans('mautic.campaign.event.triggers.header'); ?>
                    </a>
                </h4>
            </div>
            <div id="campaignEventTriggers" class="panel-collapse collapse in">
                <div class="panel-body">
                    <?php foreach ($eventSettings['grouped']['trigger'] as $group => $groupEvents): ?>
                    <div class="campaign-event-group-header"><?php echo $group; ?></div>
                    <div class="campaign-event-group-body list-group">
                        <?php foreach ($groupEvents as $k => $e): ?>
                            <a data-toggle="ajaxmodal" data-target="#campaignEventModal" class="list-group-item" href="<?php echo $view['router']->generate('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'trigger')); ?>">
                                <div class="padding-sm" data-toggle="tooltip" title="<?php echo  $view['translator']->trans($e['description']); ?>">
                                    <span><?php echo $view['translator']->trans($e['label']); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#campaignEventPanel" href="#campaignEventActions">
                        <?php echo $view['translator']->trans('mautic.campaign.event.actions.header'); ?>
                    </a>
                </h4>
            </div>
            <div id="campaignEventActions" class="panel-collapse collapse">
                <div class="panel-body">
                    <?php foreach ($eventSettings['grouped']['action'] as $group => $groupEvents): ?>
                    <div class="campaign-event-group-header"><?php echo $group; ?></div>
                    <div class="campaign-event-group-body list-group">
                    <?php foreach ($groupEvents as $k => $e): ?>
                        <a data-toggle="ajaxmodal" data-target="#campaignEventModal" class="list-group-item" href="<?php echo $view['router']->generate('mautic_campaignevent_action', array('objectAction' => 'new', 'type' => $k, 'eventType'=> 'action')); ?>">
                            <div class="padding-sm" data-toggle="tooltip" title="<?php echo  $view['translator']->trans($e['description']); ?>">
                                <span><?php echo $view['translator']->trans($e['label']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>