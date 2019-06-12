<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use DeviceDetector\Parser\Device\DeviceParserAbstract;

?>

<table class="table table-bordered table-striped mb-0">
    <thead>
        <tr>
            <th class="timeline-icon"></th>
            <th><?php echo $view['translator']->trans('mautic.lead.device.header'); ?></th>
            <th><?php echo $view['translator']->trans('mautic.lead.device_os_name.header'); ?></th>
            <th><?php echo $view['translator']->trans('mautic.lead.device_os_version.header'); ?></th>
            <th><?php echo $view['translator']->trans('mautic.lead.device_browser.header'); ?></th>
            <th><?php echo $view['translator']->trans('mautic.lead.device_brand.header'); ?></th>
            <th><?php echo $view['translator']->trans('mautic.core.date.added'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($devices as $device): ?>
        <tr>
            <td>
                <i class="fa fa-fw fa-<?php echo ('smartphone' === $device['device']) ? 'mobile' : $device['device']; ?>"></i>
            </td>
            <td><?php echo $view['translator']->transConditional('mautic.lead.device.'.$device['device'], ucfirst($device['device'])); ?></td>
            <td><?php echo $device['device_os_name']; ?></td>
            <td><?php echo $device['device_os_version']; ?></td>
            <td>
                <?php
                $clientInfo = unserialize($device['client_info']);
                echo (is_array($clientInfo) && isset($clientInfo['name'])) ? $clientInfo['name'] : '';
                ?>
            </td>
            <td><?php echo DeviceParserAbstract::getFullName($device['device_brand']); ?></td>
            <td><?php echo $view['date']->toText($device['date_added'], 'utc'); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>