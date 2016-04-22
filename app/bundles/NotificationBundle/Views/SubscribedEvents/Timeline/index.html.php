<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$notification = $event['extra']['notification'];
?>

<li class="wrapper form-submitted">
    <div class="figure"><span class="fa <?php echo isset($icons['notification']) ? $icons['notification'] : '' ?>"></span></div>
    <div class="panel">
        <div class="panel-body">
            <h3>
                <a href="<?php echo $view['router']->generate('mautic_notification_action',
                    array("objectAction" => "preview", "objectId" => $sms->getId())); ?>"
                   data-toggle="ajaxmodal" data-target="#MauticSharedModal" data-header="<?php echo $view['translator']->trans('mautic.notification.notifications.header.preview'); ?>" data-footer="false">
                    <?php echo $notification->getName(); ?>
                </a>
            </h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
        </div>
    </div>
</li>