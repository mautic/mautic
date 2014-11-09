<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//for defining the jsPlumb anchors
$class = ($event['eventType'] == 'decision') ? 'list-campaign-decision' : 'list-campaign-nondecision list-campaign-' . $event['eventType'];

if (empty($route))
    $route = 'mautic_campaignevent_action';

//generate style if applicable
$cs    = $event['canvasSettings'];
$style = (!empty($cs['droppedX'])) ? ' style="' . "position: absolute; top: {$cs['droppedY']}px; left: {$cs['droppedX']}px;" . '"' : '';
?>

<div <?php echo $style; ?> id="CampaignEvent_<?php echo $event['id'] ?>" class="draggable list-campaign-event <?php echo $class; ?>">
    <div><span class="campaign-event-name"><?php echo $event['name']; ?></span></div>
    <div class="campaign-event-buttons hide">
        <a data-toggle="ajaxmodal" data-target="#CampaignEventModal" href="<?php echo $view['router']->generate($route, array('objectAction' => 'edit', 'objectId' => $event['id'])); ?>" class="hide btn btn-success btn-xs btn-edit">
            <i class="fa fa-pencil-square-o"></i>
        </a>
        <a data-menu-link="mautic_campaign_index" data-toggle="ajax" data-ignore-formexit="true" data-method="POST" data-hide-loadingbar="true" href="<?php echo $view['router']->generate($route, array('objectAction' => 'delete', 'objectId' => $event['id'])); ?>"  class="btn  btn-delete btn-danger btn-xs">
            <i class="fa fa-times"></i>
        </a>
    </div>
</div>