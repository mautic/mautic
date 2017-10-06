<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php if ($item = ((isset($event['extra'])) ? $event['extra']['log'] : false)): ?>
    <table class="table table-striped table-sm table-condensed">
        <thead>
        <tr>
            <th><?php echo $view['translator']->trans('mautic.queued.message.timeline.channel'); ?></th>
            <th><?php echo $view['translator']->trans('mautic.queued.message.timeline.attempts'); ?></th>
            <th><?php echo $view['translator']->trans('mautic.queued.message.timeline.date.added'); ?></th>
            <th><?php echo $view['translator']->trans('mautic.queued.message.timeline.rescheduled'); ?></th>
            <th><?php echo $view['translator']->trans('mautic.queued.message.timeline.status'); ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <th scope="row"><?php echo $view['channel']->getChannelLabel($item['channelName']); ?></th>
            <td><?php echo $item['attempts']; ?></td>
            <td><?php if ($item['dateAdded']) : echo $view['date']->toFullConcat($item['dateAdded']); endif; ?></td>
            <td><?php  if ($item['scheduledDate']) : echo $view['date']->toFullConcat($item['scheduledDate']); endif; ?></td>
            <td id="queued-status-<?php echo $item['id']; ?>">
                <?php echo $view['translator']->trans('mautic.message.queue.status.'.$item['status'], [], 'javascript'); ?>
            </td>
            <td>
                <?php if ($item['status'] == 'pending') : ?>
                <button type="button" id="queued-message-<?php echo $item['id']; ?>" class="btn btn-default btn-nospin"  onclick="Mautic.cancelQueuedMessageEvent(<?php echo $item['id']; ?>)" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.queued.message.event.cancel'); ?>">
                    <i class="fa fa-times text-danger"></i>
                </button>
                <?php endif; ?>
            </td>
        </tr>
        </tbody>
    </table>
<?php endif; ?>