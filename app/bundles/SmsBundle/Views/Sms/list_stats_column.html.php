<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<span class="mt-xs label label-warning has-click-event clickable-stat"
                              data-toggle="tooltip"
                              title="<?php echo $view['translator']->trans('mautic.channel.stat.leadcount.tooltip'); ?>">
                            <a href="<?php echo $view['router']->path(
                                'mautic_contact_index',
                                ['search' => $view['translator']->trans('mautic.lead.lead.searchcommand.sms_sent').':'.$item->getId()]
                            ); ?>"><?php echo $view['translator']->trans(
                                    'mautic.sms.stat.sentcount',
                                    ['%count%' => $item->getSentCount(true)]
                                ); ?></a>
</span>

<?php

if ($transport->getSettings()->hasDelivered()) {
    ?>
    <span class="mt-xs label label-success has-click-event clickable-stat"
          data-toggle="tooltip"
          title="<?php echo $view['translator']->trans('mautic.channel.stat.leadcount.tooltip'); ?>">
                            <a href="<?php echo $view['router']->path(
                                'mautic_contact_index',
                                ['search' => $view['translator']->trans('mautic.lead.lead.searchcommand.sms_delivered').':'.$item->getId()]
                            ); ?>"><?php echo $view['translator']->trans(
                                    'mautic.sms.stat.deliveredcount',
                                    [
                                            '%count%' => $item->getDeliveredCount(),
                                            '%ratio%' => $item->getDeliveredRatio(),
                                    ]
                                ); ?></a>
                        </span>
    <?php
}
?>

<?php

if ($transport->getSettings()->hasRead()) {
    ?>
    <span class="mt-xs label label-primary has-click-event clickable-stat"
          data-toggle="tooltip"
          title="<?php echo $view['translator']->trans('mautic.channel.stat.leadcount.tooltip'); ?>">
                            <a href="<?php echo $view['router']->path(
                                'mautic_contact_index',
                                ['search' => $view['translator']->trans('mautic.lead.lead.searchcommand.sms_read').':'.$item->getId()]
                            ); ?>"><?php echo $view['translator']->trans(
                                    'mautic.sms.stat.readcount',
                                    [
                                            '%count%' => $item->getReadCount(),
                                            '%ratio%' => $item->getReadRatio(),
                                    ]
                                ); ?></a>
                        </span>
    <?php
}
?>

<?php

if ($transport->getSettings()->hasFailed()) {
    ?>
    <span class="mt-xs label label-danger has-click-event clickable-stat"
          data-toggle="tooltip"
          title="<?php echo $view['translator']->trans('mautic.channel.stat.leadcount.tooltip'); ?>">
                            <a href="<?php echo $view['router']->path(
                                'mautic_contact_index',
                                ['search' => $view['translator']->trans('mautic.lead.lead.searchcommand.sms_failed').':'.$item->getId()]
                            ); ?>"><?php echo $view['translator']->trans(
                                    'mautic.sms.stat.failedcount',
                                    [
                                            '%count%' => $item->getFailedCount(),
                                            '%ratio%' => $item->getFailedRatio(),
                                    ]
                                ); ?></a>
                        </span>
    <?php
}
?>
