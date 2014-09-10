<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$containerClass = (!empty($deleted)) ? ' bg-danger' : '';

if ($event instanceof \Mautic\CampaignBundle\Entity\CampaignEvent) {
    $name = $event->getName();
    $desc = $event->getDescription();
} else {
    $name = $event['name'];
    $desc = $event['description'];
}
?>

<li class="campaign-event-row <?php echo $containerClass; ?>" id="campaignEvent_<?php echo $id; ?>">
    <div class="campaign-event-details">
        <?php
        if (!empty($inForm))
            echo $view->render('MauticCampaignBundle:CampaignBuilder:actions.html.php', array(
                'deleted'  => (!empty($deleted)) ? $deleted : false,
                'id'       => $id,
                'route'   => 'mautic_campaignevent_action'
            ));
        ?>
        <span class="campaign-event-label"><?php echo $name; ?></span>
        <?php if (!empty($desc)): ?>
        <span class="campaign-event-descr"><?php echo $desc; ?></span>
        <?php endif; ?>
    </div>
</li>