<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel-group margin-sm-sides global-search-panel" id="global-search-panel">
<?php foreach ($results as $header => $result): ?>
<?php if (isset($result['count'])) { $count = $result['count']; unset($result['count']); } ?>
<?php $unique = uniqid(); ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a data-toggle="collapse" data-parent="#global-search-panel"
                   href="#<?php echo $unique; ?>-panel" class="collapsed">
                    <?php echo $header; ?>
                    <?php if (!empty($count)): ?>
                    <span class="badge pull-right gs-count-badge"><?php echo $count; ?></span>
                    <?php endif; ?>
                </a>
            </h4>
        </div>
        <div id="<?php echo $unique; ?>-panel" class="panel-collapse collapse">
            <div class="panel-body">
                <?php foreach ($result as $r): ?>
                <div class="row">
                    <?php echo $r; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>