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
            <span><?php echo $view['translator']->trans('mautic.chat.chat.channels'); ?></span>
            <?php if ($permissions['chat:channels:create']): ?>
            <span> - <a data-toggle="ajaxmodal" data-target="#channelModal" data-header="<?php echo $view['translator']->trans('mautic.chat.channel.header.new'); ?>" data-ignore-removemodal="true" href="<?php echo $view['router']->generate('mautic_chatchannel_action', array('objectAction' => 'new')); ?>"><?php echo $view['translator']->trans('mautic.chat.channel.new'); ?></a></span>
            <?php endif; ?>
        </h5>
    </li>
    <?php foreach ($channels as $channel): ?>
    <?php $hasUnread = (!empty($channel['stats']['unread'])) ? ' text-warning' : ''; ?>
    <li class="media-heading ml-md">
        <a href="javascript:void(0);" onclick="Mautic.startChannelChat('<?php echo $channel['id']; ?>');" class="media offcanvas-opener offcanvas-open-rtl">
            <span class="chat-channel media-heading<?php echo $hasUnread; ?>"># <?php echo $channel['name']; ?><?php if ($hasUnread): ?><span class="badge ml-sm"><?php echo $channel['stats']['unread']; ?></span><?php endif; ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>