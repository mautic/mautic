<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$lastMsg  = array('id' => 0);
$withUser = (isset($with)) ? $with->getId() : 0;
if (!empty($inPopup)):
    $view->extend('MauticCoreBundle:Default:slim.html.php');
    $view['assets']->addScriptDeclaration("Mautic.activateChatInput('{$with->getId()}', 'user');", 'bodyClose');
?>
<div id="ChatConversation">
<?php endif; ?>
    <ul class="media-list media-list-bubble" id="ChatMessages">
    <?php
    if (!empty($messages)):
        $lastMsg = end($messages);
        if (!empty($with)): ?>
        <?php echo $view->render('MauticChatBundle:User:messages.html.php', array(
            'messages'            => $messages,
            'me'                  => $me,
            'with'                => $with,
            'insertUnreadDivider' => (!empty($insertUnreadDivider)) ? true : false
        )); ?>
        <?php endif; ?>
    <?php endif; ?>
    </ul>
    <input type="hidden" id="ChatLastMessageId" value="<?php echo $lastMsg['id']; ?>" />
    <input type="hidden" id="ChatWithUserId" value="<?php echo $withUser; ?>" />

<?php if (!empty($inPopup)): ?>
</div>
<?php endif; ?>