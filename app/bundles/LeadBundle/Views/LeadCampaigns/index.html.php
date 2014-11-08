<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$leadId   = $lead->getId();
$leadName = $lead->getPrimaryIdentifier();
?>
<table class="table table-condensed table-border">
    <?php foreach ($campaigns as $c):
        $switch  = $c['inCampaign'] ? 'fa-toggle-on' : 'fa-toggle-off';
        $bgClass = $c['inCampaign'] ? 'text-success' : 'text-danger';
    ?>
    <tr>
        <td class="fa-fw">
            <i class="fa fa-2x fa-fw <?php echo $switch . ' ' . $bgClass; ?>" id="leadCampaignToggle<?php echo $c['id']; ?>"
               onclick="Mautic.toggleLeadCampaign('leadCampaignToggle<?php echo $c['id']; ?>', <?php echo $leadId; ?>, <?php echo $c['id']; ?>);"></i>
        </td>
        <td>
            <?php echo $c['name']; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>