<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo generate points log view
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.lead.lead.header.pointslog'); ?></h3>
    </div>
    <div class="table-responsive panel-collapse pull out">
        <table class="table table-hover table-bordered table-striped table-condensed">
            <thead>
                <tr>
                    <th class="col-leadpoints-date"><?php echo $view['translator']->trans('mautic.lead.lead.thead.date'); ?></th>
                    <th class="col-leadpoints-event"><?php echo $view['translator']->trans('mautic.lead.lead.thead.event'); ?></th>
                    <th class="col-leadpoints-action"><?php echo $view['translator']->trans('mautic.lead.lead.thead.action'); ?></th>
                    <th class="col-leadpoints-delta"><?php echo $view['translator']->trans('mautic.lead.lead.thead.delta'); ?></th>
                    <th class="col-leadpoints-ip"><?php echo $view['translator']->trans('mautic.lead.lead.thead.ip'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $log = $lead->getPointsChangeLog(); ?>
                <?php if (!count($log)): ?>
                <tr>
                    <td><?php echo $view['date']->toFullConcat($lead->getDateAdded()); ?></td>
                    <td><?php echo $view['translator']->trans('mautic.lead.lead.pointsevent.created'); ?></td>
                    <td></td>
                    <?php $delta = $lead->getPoints(); ?>
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
                    <td></td>
                </tr>
                <?php else: ?>
                <?php foreach($log as $e): ?>
                <tr>
                    <td><?php echo $view['date']->toFullConcat($e->getDateAdded()); ?></td>
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
                <?php endif; ?>
            </tbody>
        </table>
        </div>
</div>