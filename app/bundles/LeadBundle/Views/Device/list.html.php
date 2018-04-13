<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>

<!-- auditlog -->
<div class="table-responsive">
    <table class="table table-hover table-bordered" id="contact-devices">
        <thead>
        <tr>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                'text' => 'mautic.lead.device',
            ]);

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                'text' => 'mautic.lead.device_brand',
            ]);

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                'text' => 'mautic.lead.device_model',
            ]);

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                'text' => 'mautic.lead.device_os_name',
            ]);

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                'text' => 'mautic.core.date.added',
            ]);
            ?>
        </tr>
        <tbody>
        <?php foreach ($devices as $counter => $device): ?>
            <?php
            $counter += 1; // prevent 0
            $rowStripe = ($counter % 2 === 0) ? ' timeline-row-highlighted' : '';
            ?>
            <tr class="timeline-row<?php echo $rowStripe; ?>">
                <td><?php echo $device['device']; ?></td>
                <td><?php echo $device['device_brand']; ?></td>
                <td><?php echo $device['device_model']; ?></td>
                <td><?php
                    $os = array_filter([$device['device_os_name'], $device['device_os_version'], $device['device_os_platform']]);
                    echo implode(' ', $os);
                    ?></td>
                <td><?php echo $view['date']->toText($device['date_added'], 'local', 'Y-m-d H:i:s', true); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
