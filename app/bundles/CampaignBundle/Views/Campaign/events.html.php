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
<?php if (!empty($events) && is_array($events)) : ?>
<!-- start: trigger type event -->
<ul class="list-group campaign-event-list">
    <?php foreach ($events as $event) : ?>
        <li class="list-group-item bg-auto bg-light-xs">
            <?php $yesClass = ('action' === $event['eventType'] && 'no' === $event['decisionPath']) ? 'danger' : 'success'; ?>
            <div class="progress-bar progress-bar-<?php echo $yesClass; ?>" style="width:<?php echo $event['yesPercent']; ?>%; left: 0;"></div>
            <div class="progress-bar progress-bar-danger" style="width:<?php echo $event['noPercent']; ?>%; left: <?php echo $event['yesPercent']; ?>%"></div>
            <div class="box-layout">
                <div class="visible-sm visible-md visible-lg col-stats">
                    <span class="mt-xs label label-<?php echo $yesClass; ?>" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.report.campaign.yes.percent'); ?>"><?php echo $event['yesPercent'].'%'; ?></span> <?php if ('action' !== $event['eventType']): ?>
                        <span class="label label-danger" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.report.campaign.no.percent'); ?>"><?php echo $event['noPercent'].'%'; ?></span>
                    <?php endif; ?>
                <span class="mt-xs label label-warning" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.report.campaign.completed.actions'); ?>"><?= $event['logCount']; ?></span> <span class="mt-xs label label-default"  data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.report.campaign.pending.actions'); ?>"><?= $event['leadCount'] - $event['logCount']; ?></span>
                </div>
                    <div class="col-md-5 va-m">
                    <h5 class="fw-sb text-primary mb-xs">
                        <?php echo $event['name']; ?>
                        <?php if ('action' !== $event['eventType']): ?>
                        <small class="text-muted"><?php echo $event['percent']; ?>%</small>
                        <?php endif; ?>

                    </h5>
                    <h6 class="text-white dark-sm"><?php echo $event['description']; ?></h6>
                </div>
                <div class="col-md-3 va-m text-right">
                    <em class="text-white dark-sm"><?php echo $view['translator']->trans('mautic.campaign.'.$event['type']); ?></em>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
<!--/ end: trigger type event -->
<?php endif; ?>
