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
    <?php foreach ($lists as $l): ?>
    <?php
        $inList  = in_array($leadId, $l['leads']);
        $switch  = $inList ? 'fa-toggle-on' : 'fa-toggle-off';
        $bgClass = $inList ? 'text-success' : 'text-danger';
    ?>
    <tr>
        <td class="fa-fw">
            <i class="fa fa-2x fa-fw <?php echo $switch . ' ' . $bgClass; ?>" id="leadListToggle<?php echo $l['id']; ?>"
               onclick="Mautic.toggleLeadList('leadListToggle<?php echo $l['id']; ?>', <?php echo $leadId; ?>, <?php echo $l['id']; ?>);"></i>
        </td>
        <td>
            <?php echo $l['name'] . ' (' . $l['alias'] . ')'; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>