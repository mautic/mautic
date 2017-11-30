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
<?php if (empty($update)): ?>
<div id="CampaignEvent_<?php echo $sourceType; ?>" data-type="source" class="draggable list-campaign-source list-campaign-leadsource">
<?php endif; ?>
    <div class="campaign-event-content">
        <div><span class="campaign-event-name ellipsis"><i class="mr-sm fa fa-<?php echo ($sourceType == 'lists') ? 'list' : 'pencil-square-o'; ?>"></i><?php echo $names; ?></span></div>
    </div>
<?php if (empty($update)): ?>
    <div class="campaign-event-buttons hide">
        <a data-toggle="ajaxmodal" data-prevent-dismiss="true" data-target="#CampaignEventModal" href="<?php echo $view['router']->path('mautic_campaignsource_action', ['objectAction' => 'edit', 'objectId' => $campaignId, 'sourceType' => $sourceType]); ?>" class="btn btn-primary btn-xs btn-edit">
            <i class="fa fa-pencil"></i>
        </a>
        <a data-toggle="ajax" data-target="CampaignEvent_<?php echo $sourceType; ?>" data-ignore-formexit="true" data-method="POST" data-hide-loadingbar="true" href="<?php echo $view['router']->path('mautic_campaignsource_action', ['objectAction' => 'delete', 'objectId' => $campaignId, 'sourceType' => $sourceType]); ?>"  class="btn btn-delete btn-danger btn-xs">
            <i class="fa fa-times"></i>
        </a>
    </div>
</div>
<?php endif; ?>