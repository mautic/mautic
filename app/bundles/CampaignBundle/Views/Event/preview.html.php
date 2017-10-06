<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (empty($route)) {
    $route = 'mautic_campaignevent_action';
}

$eventType  = $event['eventType'];
$eventLogic = '';
$translator = $this->get('translator');

?>
<?php if (empty($update)): ?>
<div id="CampaignEvent_<?php echo $event['id'] ?>" data-type="<?php echo $event['eventType']; ?>" class="draggable list-campaign-event list-campaign-<?php echo $event['eventType']; ?>" data-event="<?php echo $event['type']; ?>">
<?php endif; ?>
    <div class="campaign-event-content">
        <div><span class="campaign-event-name ellipsis"><?php echo $event['name']; ?></span></div>
        <span class="campaign-event-logic"><?php echo $view['translator']->trans('mautic.campaign.'.$event['type']); ?></span>
    </div>
<?php if (empty($update)): ?>
    <div class="campaign-event-buttons hide">
        <a data-toggle="ajaxmodal" data-prevent-dismiss="true" data-target="#CampaignEventModal" href="<?php echo $view['router']->path($route, ['objectAction' => 'edit', 'objectId' => $event['id'], 'campaignId' => $campaignId]); ?>" class="btn btn-primary btn-xs btn-edit">
            <i class="fa fa-pencil"></i>
        </a>
        <a data-toggle="ajax" data-target="CampaignEvent_<?php echo $event['id'] ?>" data-ignore-formexit="true" data-method="POST" data-hide-loadingbar="true" href="<?php echo $view['router']->path($route, ['objectAction' => 'delete', 'objectId' => $event['id'], 'campaignId' => $campaignId]); ?>"  class="btn btn-delete btn-danger btn-xs">
            <i class="fa fa-times"></i>
        </a>
    </div>
</div>
<?php endif; ?>
