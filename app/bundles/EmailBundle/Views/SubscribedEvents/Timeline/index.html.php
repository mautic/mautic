<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($item = ((isset($event['extra'])) ? $event['extra']['stat'] : false)): ?>
    <p>
        <?php if (!empty($item['isFailed'])) : ?>

            <?php if (isset($item['openDetails']['bounces'])): ?>
                <span class="label label-warning" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.email.timeline.event.bounced'); ?>">
                    <?php echo $view['translator']->trans('mautic.email.timeline.event.bounced'); ?>
                </span>
                <?php else : ?>
                <?php echo $view['translator']->trans('mautic.email.timeline.event.failed'); ?>
            <?php endif; ?>
        <?php elseif (empty($item['dateRead'])) : ?>
            <?php echo $view['translator']->trans('mautic.email.timeline.event.not.read'); ?>
        <?php else : ?>
            <?php echo $view['translator']->trans(
                'mautic.email.timeline.event.'.$event['extra']['type'],
                [
                    '%date%'     => $view['date']->toFull($event['timestamp']),
                    '%interval%' => $view['date']->formatRange($event['timestamp']),
                    '%sent%'     => $view['date']->toFull($item['dateSent']),
                ]
            ); ?>
        <?php endif; ?>
        <?php if (!empty($item['viewedInBrowser'])) : ?>
            <?php echo $view['translator']->trans('mautic.email.timeline.event.viewed.in.browser'); ?>
        <?php endif; ?>
        <?php if (!empty($item['retryCount'])) : ?>
            <?php echo $view['translator']->transChoice(
                'mautic.email.timeline.event.retried',
                $item['retryCount'],
                ['%count%' => $item['retryCount']]
            ); ?>
        <?php endif; ?>
        <?php if (!empty($item['list_name'])) : ?>
            <?php echo $view['translator']->trans('mautic.email.timeline.event.list', ['%list%' => $item['list_name']]); ?>
        <?php endif; ?>
    </p>
    <div class="small">
    <?php
    if (isset($item['openDetails']['bounces'])):
        unset($item['openDetails']['bounces']);
    endif;
    ?>

    <?php if (!empty($item['openDetails'])): ?>
    <h6 class="mt-lg mb-sm"><strong><?php echo $view['translator']->trans('mautic.email.timeline.open_details'); ?></strong></h6>
    <?php
    $counter = 1;
        foreach ($item['openDetails'] as $detail):
            if (empty($showMore) && $counter > 5):
                $showMore = true;

                echo '<div style="display:none">';
            endif;
            ?>
            <?php if ($counter > 1): ?><hr/><?php endif; ?>
            <strong><?php echo $view['date']->toText($detail['datetime'], 'UTC'); ?></strong><br/><?php echo $detail['useragent']; ?>
            <?php ++$counter; ?>
        <?php endforeach; ?>
        <?php

        if (!empty($showMore)):
            echo '</div>';
            echo '<a href="javascript:void(0);" class="text-center small center-block mt-xs" onclick="Mautic.toggleTimelineMoreVisiblity(mQuery(this).prev());">';
            echo $view['translator']->trans('mautic.core.more.show');
            echo '</a>';
        endif;
        ?>
    <?php endif; ?>
    </div>
<?php endif; ?>
