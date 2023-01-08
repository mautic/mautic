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
                    value="<?php echo $view->escape($option); ?>"
                <?php if (!empty($campaignSources[$option])) {
                        echo 'disabled';
                    } ?>>
                <span><?php echo $view['translator']->trans('mautic.campaign.leadsource.'.$option); ?></span>
            </option>
        <?php endforeach; ?>
    </select>
</div>
