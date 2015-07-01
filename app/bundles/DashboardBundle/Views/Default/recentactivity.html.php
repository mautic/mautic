<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
                <div class="media-object pull-left">
                    <span class="figure featured <?php echo ($log['action'] == 'create') ? 'bg-success' : ''; ?>">
                        <span class="fa <?php echo isset($icons[$log['bundle']]) ? $icons[$log['bundle']] : '' ?>"></span>
                    </span>
                </div>
                <div class="media-body">
                    <?php if (isset($log['userId']) && $log['userId']) : ?>
                        <a href="<?php echo $view['router']->generate('mautic_user_action', array('objectAction' => 'edit', 'objectId' => $log['userId'])); ?>" data-toggle="ajax">
                            <?php echo $log['userName']; ?>
                        </a>
                    <?php elseif ($log['userName']) : ?>
                        <?php echo $log['userName']; ?>
                    <?php else: ?>
                        <?php echo $view['translator']->trans('mautic.core.system'); ?>
                    <?php endif; ?>
                    <?php echo $view['translator']->trans('mautic.dashboard.' . $log['action'] . '.past.tense'); ?>

                    <?php if (!empty($log['route'])): ?>
                    <a href="<?php echo $log['route']; ?>" data-toggle="ajax">
                        <?php echo $log['objectName']; ?>
                    </a>
                    <?php else: ?>
                    <?php echo $log['objectName']; ?>
                    <?php endif; ?>
                    <?php echo $log['object']; ?>
                    <p class="fs-12 dark-sm"><small> <?php echo $view['date']->toFull($log['dateAdded']); ?></small></p>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
