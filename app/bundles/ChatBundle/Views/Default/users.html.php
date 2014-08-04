<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="media-list media-list-contact">
<?php foreach ($users as $u): ?>
    <?php
    switch ($u['onlineStatus']):
        case 'online':
            $status = 'success';
            break;
        case 'away':
            $status = 'warning';
            break;
        case 'dnd':
            $status = 'danger';
            break;
        default:
            $status = 'default';
            break;
    endswitch;
    $hasUnread = (!empty($u['unread'])) ? ' text-warning' : '';
    ?>
    <a href="javascript:void(0);" onclick="Mautic.startChatWith('<?php echo $u['id']; ?>');" class="media offcanvas-opener offcanvas-open-rtl">
        <span class="media-object pull-left">
            <img src="<?php echo $view['gravatar']->getImage($u['email'], '40'); ?>" class="img-circle" alt="" />
        </span>
        <span class="media-body">
            <span class="media-heading<?php echo $hasUnread; ?>"><span class="hasnotification hasnotification-<?php echo $status; ?> mr5"></span><?php echo $u['firstName'] . ' ' . $u['lastName']; ?></span>

            <span class="media-meta ellipsis"><?php echo $view['translator']->trans('mautic.chat.chat.status.'.$u['onlineStatus']); ?></span>
        </span>
    </a>
<?php endforeach; ?>
</div>