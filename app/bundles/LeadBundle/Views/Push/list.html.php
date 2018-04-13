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
    <table class="table table-hover table-bordered" id="contact-pushids">
        <thead>
        <tr>
            <?php
            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'text' => 'mautic.notification.notifications.device.id',
                ]
            );

            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'text' => 'mautic.notification.notifications.device.enabled',
                ]
            );

            echo $view->render(
                'MauticCoreBundle:Helper:tableheader.html.php',
                [
                    'text' => 'mautic.notification.notifications.device.mobile',
                ]
            );
            ?>
        </tr>
        <tbody>
        <?php
        /** @var \Mautic\NotificationBundle\Entity\PushID[] $pushIds */
        foreach ($pushIds as $counter => $pushId): ?>
            <?php
            $counter += 1; // prevent 0
            $rowStripe = ($counter % 2 === 0) ? ' timeline-row-highlighted' : '';
            ?>
            <tr class="timeline-row<?php echo $rowStripe; ?>">
                <td><?php echo $pushId->getPushId(); ?></td>
                <td><?php
                    $class = 'fa fa-times';
                    if ($pushId->isEnabled()) {
                        $class = 'fa fa-check';
                    }
                    ?>
                    <i class="<?php echo $class ?>"></i>
                </td>
                <td><?php
                    $class = 'fa fa-times';
                    if ($pushId->isMobile()) {
                        $class = 'fa fa-check';
                    }
                    ?>
                    <i class="<?php echo $class ?>"></i>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
