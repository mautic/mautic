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
                            [
                                    'objectAction' => 'new',
                                    'type'         => $k,
                                    'eventType'    => $eventGroup,
                                    'campaignId'   => $campaignId,
                                    'anchor'       => '',
                            ]
                        ); ?>"
                        data-target="#CampaignEventModal"
                        title="<?php echo $view->escape($e['description']); ?>"
                        value="<?php echo $view->escape($k); ?>">
                    <span><?php echo $e['label']; ?></span>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
<?php endforeach; ?>
