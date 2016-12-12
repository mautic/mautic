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

<div class="panel-group" id="globalSearchPanel">
<?php foreach ($results as $header => $result): ?>
<?php if (isset($result['count'])) {
    $count = $result['count'];
    unset($result['count']);
} ?>
    <div class="panel panel-info">
        <div class="panel-heading">
            <h4 class="panel-title">
                <?php echo $header; ?>
                <?php if (!empty($count)): ?>
                <span class="badge pull-right gs-count-badge"><?php echo $count; ?></span>
                <?php endif; ?>
            </h4>
        </div>
        <div class="panel-body np">
            <ul class="list-group">
                <?php foreach ($result as $r): ?>
                <li class="list-group-item">
                    <?php echo $r; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endforeach; ?>
</div>