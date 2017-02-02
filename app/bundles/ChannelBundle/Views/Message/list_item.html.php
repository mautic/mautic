<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \Mautic\ChannelBundle\Entity\Message $item */
$messageChannels = $item->getChannels();
$channels        = [];
if ($messageChannels) {
    foreach ($messageChannels as $channelName => $channel) {
        if (!$channel->getChannelId()) {
            continue;
        }

        $channels[] = $view['translator']->hasId('mautic.channel.'.$channelName)
            ? $view['translator']->trans('mautic.channel.'.$channelName)
            : ucfirst(
                $channelName
            );
    }
}
?>

<td class="visible-md visible-lg">
    <?php foreach ($channels as $channel): ?>
    <span class="label label-default"><?php echo $channel; ?></span>
    <?php endforeach; ?>
</td>