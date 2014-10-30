<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//for defining the jsPlumb anchors
$class = ($event['eventType'] == 'leadaction') ? 'decision' : 'nondecision';


if (!empty($deleted)):
    $action    = 'undelete';
    $iconClass = 'fa-undo';
    $btnClass  = 'btn-warning';
else:
    $action    = 'delete';
    $iconClass = 'fa-times';
    $btnClass  = 'btn-danger';
endif;

if (empty($route))
    $route = 'mautic_campaignevent_action';

//generate style if applicable
$cs    = $event['canvasSettings'];
$style = (!empty($cs['droppedX'])) ? ' style="' . "position: absolute; top: {$cs['droppedY']}px; left: {$cs['droppedX']}px;" . '"' : '';
?>

<div <?php echo $style; ?> id="CampaignEvent_<?php echo $event['id'] ?>" class="draggable list-campaign-event list-campaign-<?php echo $class; ?> list-campaign-<?php echo $event['eventType']; ?>">
    <span class="campaign-event-name"><?php echo $event['name']; ?></span>

    <div class="campaign-event-buttons hide">
        <a data-toggle="ajaxmodal" data-target="#CampaignEventModal" href="<?php echo $view['router']->generate($route, array('objectAction' => 'edit', 'objectId' => $event['id'])); ?>" class="btn btn-success btn-xs btn-edit">
            <i class="fa fa-pencil-square-o"></i>
        </a>
        <a data-menu-link="mautic_campaign_index" data-toggle="ajax" data-ignore-formexit="true" data-method="POST" data-hide-loadingbar="true" href="<?php echo $view['router']->generate($route, array('objectAction' => $action, 'objectId' => $event['id'])); ?>"  class="btn <?php echo $btnClass; ?> btn-xs">
            <i class="fa <?php echo $iconClass; ?>"></i>
        </a>
    </div>
</div>