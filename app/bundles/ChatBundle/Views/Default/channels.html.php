<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<ul class="media-list media-list-contact" id="ChatChannels">
    <?php foreach ($channels as $channel): ?>
    <?php $hasUnread = (!empty($channel['stats']['unread'])) ? ' text-warning' : ''; ?>
    <li class="media-heading">
        <a href="javascript:void(0);" onclick="Mautic.startChannelChat('<?php echo $channel['id']; ?>');" class="media offcanvas-opener offcanvas-open-rtl">
            <span class="media-heading<?php echo $hasUnread; ?>"># <?php echo $channel['name']; ?></span>
            <?php if ($hasUnread): ?><span class="badge"><?php echo $channel['stats']['unread']; ?></span><?php endif; ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>