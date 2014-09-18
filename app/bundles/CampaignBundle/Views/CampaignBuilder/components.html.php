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
                    <?php foreach ($eventSettings['trigger'] as $k => $e): ?>
                        <?php if ($newGroup = (empty($lastGroup) || $lastGroup != $e['group'])): ?>
                            <div class="campaign-event-group-header"><?php echo $e['group']; ?></div>
                            <div class="campaign-event-group-body">
                        <?php endif; ?>
                        <a data-toggle="ajaxmodal" data-target="#campaignEventModal" href="<?php echo $view['router']->generate(
                            'mautic_campaignevent_action', array(
                                'objectAction' => 'new',
                                'type'         => $k,
                                'eventType'    => 'trigger'
                            )); ?>">
                            <div class="page-list-item">
                                <div class="padding-sm">
                                    <div class="pull-left padding-sm">
                                        <span class="list-item-primary"><?php echo $view['translator']->trans($e['label']); ?></span>
                                        <?php if (isset($e['description'])): ?>
                                            <span class="list-item-secondary"><?php echo  $view['translator']->trans($e['description']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pull-right padding-sm">
                                        <i class="fa fa-fw fa-plus fa-lg"></i>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </a>
                        <?php if ($newGroup): ?>
                            </div>
                        <?php endif; ?>
                        <?php $lastGroup = $e['group']; ?>
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
                    <?php foreach ($eventSettings['action'] as $k => $e): ?>
                        <?php if ($newGroup = (empty($lastGroup) || $lastGroup != $e['group'])): ?>
                            <div class="campaign-event-group-header"><?php echo $e['group']; ?></div>
                            <div class="campaign-event-group-body">
                        <?php endif; ?>
                        <a data-toggle="ajaxmodal" data-target="#campaignEventModal" href="<?php echo $view['router']->generate(
                            'mautic_campaignevent_action', array(
                                'objectAction' => 'new',
                                'type'         => $k,
                                'eventType'    => 'action'
                            )); ?>">
                            <div class="page-list-item">
                                <div class="padding-sm">
                                    <div class="pull-left padding-sm">
                                        <span class="list-item-primary"><?php echo $view['translator']->trans($e['label']); ?></span>
                                        <?php if (isset($e['description'])): ?>
                                            <span class="list-item-secondary"><?php echo  $view['translator']->trans($e['description']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pull-right padding-sm">
                                        <i class="fa fa-fw fa-plus fa-lg"></i>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </a>
                        <?php if ($newGroup): ?>
                            </div>
                        <?php endif; ?>
                        <?php $lastGroup = $e['group']; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>