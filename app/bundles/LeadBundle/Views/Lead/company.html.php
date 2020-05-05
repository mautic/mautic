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
    <?php foreach ($companies as $company): ?>
        <?php
        $switch  = isset($companyLead[$company['id']]) ? 'fa-toggle-on' : 'fa-toggle-off';
        $bgClass = isset($companyLead[$company['id']]) ? 'text-success' : 'text-danger';
        ?>
        <li class="list-group-item">
            <i class="fa fa-lg fa-fw <?php echo $switch.' '.$bgClass; ?>" id="companyLeadsToggle<?php echo $company['id']; ?>" onclick="Mautic.toggleCompanyLead('companyLeadsToggle<?php echo $company['id']; ?>', <?php echo $leadId; ?>, <?php echo $company['id']; ?>);"></i>
            <span><?php echo $view->escape($company['companyname']); ?></span>
        </li>
    <?php endforeach; ?>
</ul>