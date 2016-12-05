<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$leadId   = $lead->getId();
$leadName = $lead->getPrimaryIdentifier();
?>
<ul class="list-group">
    <?php foreach ($campaigns as $c):
        $switch  = $c['inCampaign'] ? 'fa-toggle-on' : 'fa-toggle-off';
        $bgClass = $c['inCampaign'] ? 'text-success' : 'text-danger';
    ?>
    <li class="list-group-item">
        <i class="fa fa-lg fa-fw <?php echo $switch.' '.$bgClass; ?>" id="leadCampaignToggle<?php echo $c['id']; ?>" onclick="Mautic.toggleLeadCampaign('leadCampaignToggle<?php echo $c['id']; ?>', <?php echo $leadId; ?>, <?php echo $c['id']; ?>);"></i>
        <span><?php echo $c['name']; ?></span>
    </li>
    <?php endforeach; ?>
</ul>