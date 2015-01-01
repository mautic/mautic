<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<ul class="media-list media-list-contact" id="ChatUsers">
    <li class="media-heading">
        <h5 class="fw-sb">
            <a data-toggle="ajaxmodal" data-target="#MauticSharedModal" data-header="<?php echo $view['translator']->trans('mautic.chat.user.modal.header'); ?>" href="<?php echo $view['router']->generate('mautic_chat_action', array('objectAction' => 'list')); ?>">
                <?php echo $view['translator']->trans('mautic.chat.chat.users'); ?>
            </a>
            <?php if (!empty($users['unread']['hidden'])): ?> <span class="label label-primary label-as-badge pull-right"><?php echo $users['unread']['hidden']; ?></span><?php endif; ?>
        </h5>
    </li>

    <?php foreach ($users['users'] as $u): ?>
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
        $hasUnread = (!empty($users['unread']['users'][$u['id']]));
        $textClass =  $hasUnread ? ' text-warning' : ' text-white';

        $name      = $u['username'];
        $shortName = (strlen($name) > 15) ? substr($name, 0, 12) . '...' : $name;
        ?>
        <li class="media chat-list pb-0 pt-0 sortable" id="chatUser_<?php echo $u['id']; ?>">
            <a href="javascript:void(0);" onclick="Mautic.startUserChat('<?php echo $u['id']; ?>');" class="media offcanvas-opener offcanvas-open-rtl">
                <span class="pull-left img-wrapper img-circle mr-sm">
                    <img src="<?php echo $view['gravatar']->getImage($u['email'], '40'); ?>" class="media-object img-circle" alt="" />
                </span>
                <span class="media-body">
                    <span class="media-heading mb-0 text-nowrap <?php echo $textClass; ?>">
                        <span class="bullet bullet-<?php echo $status; ?> chat-bullet mr-sm"></span><?php echo $shortName; ?><?php if ($hasUnread): ?> <span class="label label-primary label-as-badge pull-right"><?php echo $users['unread']['users'][$u['id']]; ?></span><?php endif; ?>
                    </span>
                    <span class="meta text-white dark-lg small"><?php echo $view['translator']->trans('mautic.chat.chat.status.'.$u['onlineStatus']); ?></span>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
