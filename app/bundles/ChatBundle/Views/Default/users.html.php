<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<ul class="media-list media-list-contact" id="ChatUsers">
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
        <li class="media">
            <a href="javascript:void(0);" onclick="Mautic.startUserChat('<?php echo $u['id']; ?>');" class="media offcanvas-opener offcanvas-open-rtl">
                <span class="pull-left img-wrapper img-rounded mr-sm">
                    <img class="media-object" src="<?php echo $view['gravatar']->getImage($u['email'], '40'); ?>" class="img-circle" alt="" />
                </span>
                <span class="media-body">
                    <span class="media-heading mb-0 text-white dark-sm<?php echo $hasUnread; ?>"><span class="bullet bullet-warning mr-xs<?php echo $status; ?>"></span><?php echo $u['firstName'] . ' ' . $u['lastName']; ?></span>
                    <span class="meta text-white dark-lg"><?php echo $view['translator']->trans('mautic.chat.chat.status.'.$u['onlineStatus']); ?></span>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>