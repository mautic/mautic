<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!empty($inPopup)) {
    $view->extend('MauticCoreBundle:Default:slim.html.php');
    //$view['assets']->addScriptDeclaration("Mautic.activateChatInput('{$with->getId()}');", 'bodyClose');
}

if (empty($contentOnly)) {
    $view['assets']->addScript('addons/MauticChatBundle/Assets/js/chats.js', 'bodyClose');
    $view['assets']->addScriptDeclaration('Mautic.activateChatListUpdate();', 'bodyClose');
}
?>
<?php if ($tmpl == 'index'): ?>
<div class="row ml-5 mr-5">
    <div class="col-xs-12">
        <?php $myStatus = $me->getOnlineStatus(); ?>
        <select class="form-control input-sm" onchange="Mautic.setChatOnlineStatus(this.value);">
            <option value="online"<?php echo ($myStatus != 'manualaway' && $myStatus != 'dnd') ? ' selected' : ''; ?>><?php echo $view['translator']->trans('mautic.chat.chat.status.online'); ?></option>
            <option value="manualaway"<?php echo ($myStatus == 'manualaway') ? ' selected' : ''; ?>><?php echo $view['translator']->trans('mautic.chat.chat.status.manualaway'); ?></option>
            <option value="dnd"<?php echo ($myStatus == 'dnd') ? ' selected' : ''; ?>><?php echo $view['translator']->trans('mautic.chat.chat.status.dnd'); ?></option>
        </select>
    </div>
</div>
<div id="ChatCanvasContent">
    <style type="text/css" scoped>
        .chat-new-divider {
            text-align: center;
            color: #00b6ad;
        }

        .chat-channel {
            font-size: 14px;
        }

        li.chat-list:after {
            border-bottom: 0 !important;
        }

        .chat-list .bullet {
            height: 8px;
            width: 8px;
        }

        /** Need to update this CSS */
        #ChatConversation .chat-group {
            padding-top: 0 !important;
        }

        #ChatUsers li.chat-list img.media-object {
            width: 40px;
        }

        .col-chat-settings {
            width: 25px;
        }

        .col-chat-count {
            width: 25px;
        }
    </style>
<?php endif; ?>

<?php echo $view->render('MauticChatBundle:Default:channels.html.php', array(
    'channels'    => $channels,
    'permissions' => $permissions
)); ?>
<?php echo $view->render('MauticChatBundle:Default:users.html.php', array(
    'users'  => $users
)); ?>

<?php if ($tmpl == 'index'): ?>
</div>
<?php endif; ?>