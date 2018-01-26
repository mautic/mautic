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

<li class="dropdown dropdown-custom" id="notificationsDropdown">
    <a href="javascript: void(0);" onclick="Mautic.showNotifications();" class="dropdown-toggle dropdown-notification" data-toggle="dropdown">
        <?php $hideClass = (!empty($updateMessage['isNew']) || !empty($showNewIndicator)) ? '' : ' hide'; ?>
        <span class="label label-danger<?php echo $hideClass; ?>" id="newNotificationIndicator"><i class="fa fa-asterisk"></i></span>
        <span class="fa fa-bell fs-16"></span>
    </a>
    <div class="dropdown-menu">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="panel-title">
                    <h6 class="fw-sb"><?php echo $view['translator']->trans('mautic.core.notifications'); ?>
                        <a href="javascript:void(0);" class="btn btn-default btn-xs btn-nospin pull-right text-danger" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.notifications.clearall'); ?>" onclick="Mautic.clearNotification(0);"><i class="fa fa-times"></i></a>
                    </h6>
                </div>
            </div>
            <div class="pt-0 pb-xs pl-0 pr-0">
                <div class="scroll-content slimscroll" style="height:250px;" id="notifications">
                    <?php echo $view->render('MauticCoreBundle:Notification:notification_messages.html.php', [
                        'notifications' => $notifications,
                        'updateMessage' => $updateMessage,
                    ]); ?>
                    <?php $class = (!empty($notifications)) ? ' hide' : ''; ?>
                    <div style="width: 100px; margin: 75px auto 0 auto;" class="<?php echo $class; ?> mautibot-image" id="notificationMautibot">
                        <img class="img img-responsive" src="<?php echo $view['mautibot']->getImage('wave'); ?>" />
                    </div>
                </div>
            </div>
            <?php $lastNotification = reset($notifications); ?>
            <input id="mauticLastNotificationId" type="hidden" value="<?php echo $view->escape($lastNotification['id']); ?>" />
        </div>
    </div>
</li>
