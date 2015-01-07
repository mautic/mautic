<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!empty($showMore)):
echo $view['translator']->trans('mautic.chat.search.more', array('%remaining%' => $remaining));

else:
    $name = $chat['fromUser']['firstName'] . ' ' . substr($chat['fromUser']['lastName'], 0, 1) . '.';
    $header = (isset($chat['channel'])) ? $view['translator']->trans('mautic.chat.channel.notification.header', array('%name%' => $chat['channel']['name'], '%from%' => $name)) : $view['translator']->trans('mautic.chat.chat.notification.header', array('%name%' => $name));
    $image = $view['gravatar']->getImage($chat['fromUser']['email'], 100);
?>
<div>
    <span class="pull-left pr-xs pt-xs" style="width:36px">
        <span class="img-wrapper img-rounded"><img src="<?php echo $image; ?>" /></span>
    </span>
    <strong><?php echo $header; ?></strong><br /><?php echo $chat['message']; ?><br />
    <small><?php echo $view['date']->toText($chat['dateSent']); ?></small>
    <div class="clearfix"></div>
</div>

<?php endif; ?>