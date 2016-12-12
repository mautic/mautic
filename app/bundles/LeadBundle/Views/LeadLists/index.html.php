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
    <?php foreach ($lists as $l): ?>
    <?php
        $inList  = isset($leadsLists[$l['id']]);
        $switch  = $inList ? 'fa-toggle-on' : 'fa-toggle-off';
        $bgClass = $inList ? 'text-success' : 'text-danger';
    ?>
    <li class="list-group-item">
        <i class="fa fa-lg fa-fw <?php echo $switch.' '.$bgClass; ?>" id="leadListToggle<?php echo $l['id']; ?>" onclick="Mautic.toggleLeadList('leadListToggle<?php echo $l['id']; ?>', <?php echo $leadId; ?>, <?php echo $l['id']; ?>);"></i>
        <span><?php echo $l['name'].' ('.$l['alias'].')'; ?></span>
    </li>
    <?php endforeach; ?>
</ul>