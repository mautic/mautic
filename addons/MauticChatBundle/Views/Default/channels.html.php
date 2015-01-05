<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<ul class="media-list media-list-contact" id="ChatChannels">
    <li class="media-heading">
        <h5 class="fw-sb">
            <a data-toggle="ajaxmodal" data-target="#MauticSharedModal" data-header="<?php echo $view['translator']->trans('mautic.chat.channel.modal.header'); ?>" href="<?php echo $view['router']->generate('mautic_chatchannel_action', array('objectAction' => 'list')); ?>">
                <span><?php echo $view['translator']->trans('mautic.chat.chat.channels'); ?></span>
            </a>
            <?php if (!empty($channels['unread']['hidden'])): ?> <span class="label label-primary label-as-badge pull-right"><?php echo $channels['unread']['hidden']; ?></span><?php endif; ?>
        </h5>
    </li>
    <?php foreach ($channels['channels'] as $channel): ?>
    <?php $hasUnread = (!empty($channels['unread']['channels'][$channel['id']])) ? ' text-warning' : ''; ?>
        <li class="media-heading ml-md pb-0 sortable" id="chatChannel_<?php echo $channel['id']; ?>">
        <a href="javascript:void(0);" onclick="Mautic.startChannelChat('<?php echo $channel['id']; ?>');" class="media offcanvas-opener offcanvas-open-rtl text-white">
            <span class="chat-channel media-heading<?php echo $hasUnread; ?>"># <?php echo $channel['name']; ?><?php if ($hasUnread): ?><span class="label label-primary label-as-badge pull-right"><?php echo $channels['unread']['channels'][$channel['id']]; ?></span><?php endif; ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>