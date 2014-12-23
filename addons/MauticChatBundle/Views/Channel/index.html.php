<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$lastMsg   = array('id' => 0);
$channelId = (isset($channel)) ? $channel->getId() : 0;
if (!empty($inPopup)):
    $view->extend('MauticCoreBundle:Default:slim.html.php');
    $view['assets']->addScriptDeclaration("Mautic.activateChatInput('{$channelId->getId()}', 'channel');", 'bodyClose');
?>
<div id="ChatConversation">
<?php endif; ?>
    <div id="ChatHeader"></div>
    <ul class="media-list media-list-bubble" id="ChatMessages">
    <?php
    if (!empty($messages)):
        $lastMsg = end($messages);
        if (!empty($channelId)): ?>
        <?php echo $view->render('MauticChatBundle:Channel:messages.html.php', array(
            'messages'            => $messages,
            'me'                  => $me,
            'channel'             => $channel,
            'insertUnreadDivider' => (!empty($insertUnreadDivider)) ? true : false,
            'lastReadId'          => $lastReadId
        )); ?>
        <?php endif; ?>
    <?php endif; ?>
    </ul>
    <input type="hidden" id="ChatLastMessageId" value="<?php echo $lastMsg['id']; ?>" />
    <input type="hidden" id="ChatChannelId" value="<?php echo $channelId; ?>" />

<?php if (!empty($inPopup)): ?>
</div>
<?php endif; ?>
