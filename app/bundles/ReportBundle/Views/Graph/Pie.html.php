<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>

<div class="panel ovf-h bg-auto bg-light-xs">
    <div class="panel-body box-layout pb-0">
        <div class="col-xs-8 va-m">
            <h5 class="dark-md fw-sb mb-xs">
                <?php echo $view['translator']->trans($graph['name']); ?>
            </h5>
        </div>
        <div class="col-xs-4 va-t text-right">
            <h3 class="text-white dark-sm"><span class="fa <?php echo isset($graph['iconClass']) ? $graph['iconClass'] : ''; ?>"></span></h3>
        </div>
    </div>
    <div class="text-center">
        <canvas 
            class="graph graph-pie"
            id="<?php echo str_replace('.', '-', $graph['name']); ?>" 
            width="210" 
            height="210">
        </canvas>
        <div id="<?php echo str_replace('.', '-', $graph['name']); ?>-data" class="hide"><?php echo json_encode($graph['data']); ?></div>
    </div>
</div>
