<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$containerClass = (!empty($deleted)) ? ' bg-danger' : '';
?>

<li class="campaign-event-row <?php echo $containerClass; ?>" id="campaignEvent<?php echo $id; ?>">
    <div>
        <?php
        if (!empty($inForm))
            echo $view->render('MauticCampaignBundle:CampaignBuilder:actions.html.php', array(
                'deleted'  => (!empty($deleted)) ? $deleted : false,
                'id'       => $id,
                'route'   => 'mautic_campaignevent_action'
            ));
        ?>
        <span class="campaign-event-label"><?php echo $action['name']; ?></span>
        <?php if (!empty($action['description'])): ?>
        <span class="campaign-event-descr"><?php echo $action['description']; ?></span>
        <?php endif; ?>
    </div>
</li>