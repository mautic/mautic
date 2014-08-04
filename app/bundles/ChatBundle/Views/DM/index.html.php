<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!empty($inPopup)):
    $view->extend('MauticCoreBundle:Default:slim.html.php');
    $view['slots']->addScriptDeclaration("Mautic.activateChatInput('{$with->getId()}');", 'bodyClose');
?>
<div id="ChatConversation">
<?php endif; ?>
<?php
if (!empty($messages)):
    $lastMsg = end($messages);
    if (!empty($with)): ?>
    <ul class="media-list media-list-bubble" id="ChatMessages">
        <?php echo $view->render('MauticChatBundle:DM:messages.html.php', array(
            'messages'            => $messages,
            'me'                  => $me,
            'with'                => $with,
            'insertUnreadDivider' => (!empty($insertUnreadDivider)) ? true : false
        )); ?>
    </ul>
    <?php endif; ?>
    <input type="hidden" id="ChatLastMessageId" value="<?php echo $lastMsg['id']; ?>" />
<?php endif; ?>

<?php if (!empty($inPopup)): ?>
</div>
<?php endif; ?>