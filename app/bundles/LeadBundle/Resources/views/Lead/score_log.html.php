<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo generate score log view
?>

<div class="panel panel-success">
    <div class="panel-heading"><?php echo $view['translator']->trans('mautic.lead.lead.header.scorelog'); ?></div>
    <div class="panel-body">
        <div class="table-responsive">
        <table class="table table-hover table-bordered table-striped table-condensed">
            <thead>
                <tr>
                    <th class="col-leadscore-date"><?php echo $view['translator']->trans('mautic.lead.lead.thead.date'); ?></th>
                    <th class="col-leadscore-event"><?php echo $view['translator']->trans('mautic.lead.lead.thead.event'); ?></th>
                    <th class="col-leadscore-action"><?php echo $view['translator']->trans('mautic.lead.lead.thead.action'); ?></th>
                    <th class="col-leadscore-delta"><?php echo $view['translator']->trans('mautic.lead.lead.thead.delta'); ?></th>
                    <th class="col-leadscore-ip"><?php echo $view['translator']->trans('mautic.lead.lead.thead.ip'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $log = $lead->getScoreChangeLog(); ?>
                <?php foreach($log as $e): ?>
                <tr>
                    <?php $date = $e->getDateAdded(); ?>
                    <td><?php echo $date->format($dateFormats['date']) . ' ' . $date->format($dateFormats['time']); ?></td>
                    <td><?php echo $e->getEventName(); ?></td>
                    <td><?php echo $e->getActionName(); ?></td>
                    <?php $delta = $e->getDelta(); ?>
                    <?php if ($delta > 0): ?>
                    <td class="success">
                        <i class="fa fa-fw fa-hand-o-up"></i><?php echo $delta; ?>
                    </td>
                    <?php elseif ($delta < 0): ?>
                    <td class="danger">
                        <i class="fa fa-fw fa-hand-o-down"></i><?php echo ($delta * -1); ?>
                    </td>
                    <?php else: ?>
                    <td>
                        <?php echo $delta; ?>
                    </td>
                    <?php endif; ?>
                    <td><?php echo $e->getIpAddress()->getIpAddress(); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>