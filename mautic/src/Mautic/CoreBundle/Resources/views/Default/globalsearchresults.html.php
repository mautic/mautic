<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel-group margin-sm-sides global-search-panel" id="global-search-panel">
<?php foreach ($results as $header => $result): ?>
<?php $unique = uniqid(); ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a data-toggle="collapse" data-parent="#global-search-panel" href="#<?php echo $unique; ?>-panel" class="collapsed"><?php echo $view['translator']->trans($header); ?></a>
            </h4>
        </div>
        <div id="<?php echo $unique; ?>-panel" class="panel-collapse collapse in">
            <div class="panel-body padding-md-sides">
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