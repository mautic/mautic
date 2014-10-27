<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mb-0">
    <div class="panel-heading">
        <div class="panel-title"><?php echo $view['translator']->trans('mautic.core.recent.activity'); ?></div>
    </div>
    <div class="panel-body pt-xs">
        <?php if (isset($logs) && $logs) : ?>
        <ul class="media-list media-list-feed">
            <?php foreach ($logs as $log) : ?>
            <li class="media">
                <div class="media-object pull-left mt-xs">
                <?php if ($log['action'] == 'create') : ?>
                    <span class="figure featured bg-success"><span class="fa fa-check"></span></span>
                <?php else: ?>
                    <span class="figure"></span>
                <?php endif; ?>
                </div>
                <div class="media-body">
                    <?php echo $log['userName']; ?>
                    <?php echo $log['action']; ?>
                    <a href="<?php echo $view['router']->generate('mautic_' . $log['bundle'] . '_action', array('objectAction' => 'view', 'objectId' => $log['objectId'])); ?>" data-toggle="ajax">
                        <?php echo $log['objectName']; ?>
                    </a>
                    <?php echo $log['object']; ?>
                    <p class="fs-12 dark-sm"><small> <?php echo $view['date']->toFull($log['dateAdded']); ?></small></p>
                    <dl>
                    <?php foreach ($log['details'] as $key => $detail) : ?>
                        <dt>
                            <?php echo ucfirst(preg_replace('/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]/', ' $0', $key)); ?>:
                        </dt>
                        <dd>
                            <?php if ($view['translator']->trans($detail[0])) : ?>
                            <del><?php echo $view['translator']->trans($detail[0]); ?></del> <i class="fa fa-long-arrow-right"></i>
                            <?php endif; ?>
                            <?php echo $view['translator']->trans($detail[1]); ?>
                        </dd>
                    <?php endforeach; ?>
                    </dl>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>