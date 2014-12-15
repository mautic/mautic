<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$firstMsg = reset($messages);
$showDate = (!empty($showDate)) ? true : false;

$dividerInserted = false;
if (!empty($insertUnreadDivider) && !$firstMsg['isRead']) {
    echo $view->render('MauticChatBundle:User:newdivider.html.php');
    $dividerInserted = true;
}
?>
<li class="media<?php echo $direction; ?> chat-group" id="ChatGroup<?php echo $firstMsg['id']; ?>">
    <a href="javascript:void(0);" class="media-object">
        <img src="<?php echo $view['gravatar']->getImage($user['email'], 40); ?>" class="img-circle" alt="">
    </a>

    <div class="media-body">
        <?php
        foreach ($messages as $message):
            if (!empty($insertUnreadDivider) && !$dividerInserted && !$message['isRead']):
                echo $view->render('MauticChatBundle:User:newdivider.html.php', array('tag' => 'div'));
                $dividerInserted = true;
            endif;
            echo $view->render('MauticChatBundle:User:message.html.php', array('message' => $message));
        endforeach;
        ?>
        <?php if ($showDate): ?>
        <p class="media-meta text-white dark-lg"><?php echo $view['date']->toShort($message['dateSent']); ?></p>
        <?php endif; ?>
    </div>
    <input type="hidden" class="chat-group-firstid" value="<?php echo $firstMsg['id']; ?>" />
</li>