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
                <?php if ($log['action'] == 'create') : ?>
                    <span class="figure featured bg-success"><span class="fa fa-check"></span></span>
                <?php else: ?>
                    <span class="figure"></span>
                <?php endif; ?>
                </div>
                <div class="media-body">
                    <?php echo $view['translator']->trans('mautic.core.'.$log['action'].'.by.past.tense'); ?>
                    <?php if (!empty($log['userId'])) : ?>
                        <a href="<?php echo $view['router']->path('mautic_user_action', ['objectAction' => 'edit', 'objectId' => $log['userId']]); ?>" data-toggle="ajax">
                            <?php echo $log['userName']; ?>
                        </a>
                    <?php else: ?>
                        <?php echo $log['userName']; ?>
                    <?php endif; ?>
                    <p class="fs-12 dark-sm"><small> <?php echo $view['date']->toFull($log['dateAdded']); ?></small></p>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
