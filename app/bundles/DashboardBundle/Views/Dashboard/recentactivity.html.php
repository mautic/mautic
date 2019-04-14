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

<?php if (isset($logs) && $logs) : ?>
<div class="pt-md pr-md pb-md pl-md">
    <ul class="media-list media-list-feed">
        <?php foreach ($logs as $log) : ?>
        <?php // If the data are loaded from cache array:?>
        <?php if (is_array($log['dateAdded']) && isset($log['dateAdded']['date'])) {
    $log['dateAdded'] = new \DateTime($log['dateAdded']['date'], (new \DateTimeZone($log['dateAdded']['timezone'])));
} ?>
        <li class="media">
            <div class="media-object pull-left">
                <span class="figure featured <?php echo ($log['action'] == 'create') ? 'bg-success' : ''; ?>">
                    <span class="fa <?php echo isset($icons[$log['bundle']]) ? $icons[$log['bundle']] : '' ?>"></span>
                </span>
            </div>
            <div class="media-body">
                <?php if (isset($log['userId']) && $log['userId']) : ?>
                    <a href="<?php echo $view['router']->path('mautic_user_action', ['objectAction' => 'edit', 'objectId' => $log['userId']]); ?>" data-toggle="ajax">
                        <?php echo $log['userName']; ?>
                    </a>
                <?php elseif ($log['userName']) : ?>
                    <?php echo $log['userName']; ?>
                <?php else: ?>
                    <?php echo $view['translator']->trans('mautic.core.system'); ?>
                <?php endif; ?>
                <?php echo $view['translator']->trans('mautic.dashboard.'.$log['action'].'.past.tense'); ?>

                <?php if (!empty($log['route'])): ?>
                <a href="<?php echo $log['route']; ?>" data-toggle="ajax">
                    <?php echo $log['objectName']; ?>
                </a>
                <?php elseif (!empty($log['objectName'])): ?>
                    <?php echo $log['objectName']; ?>
                <?php endif; ?>
                <?php echo $log['object']; ?>
                <p class="fs-12 dark-sm"><small> <?php echo $view['date']->toFull($log['dateAdded']); ?></small></p>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
