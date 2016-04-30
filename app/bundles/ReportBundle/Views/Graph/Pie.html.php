<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>

<div class="col-md-4">
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
            <canvas class="graph graph-pie" id="<?php echo str_replace('.', '-', $graph['name']); ?>" width="210" height="210">
            </canvas>
            <div id="<?php echo str_replace('.', '-', $graph['name']); ?>-data" class="hide">
                <?php echo json_encode($graph['data']); ?>
            </div>
            <div class="labels pb-10">
                <?php if (isset($graph['data']) && $graph['data']) : ?>
                    <?php foreach ($graph['data'] as $item) : ?>
                        <?php $style = 'style="'; ?>
                        <?php if (isset($item['color'])) : ?>
                            <?php $style .= 'background:' . $item['color']; ?>
                        <?php endif; ?>
                        <?php $style .= '"'; ?>
                        <span class="label label-default" <?php echo $style; ?>>
                            <?php $label = (isset($options['translate']) && $options['translate'] === false) ? $item['label'] : $view['translator']->trans($graph['name'] . '.' . $item['label']); ?>
                            <?php echo $label; ?>:
                            <?php if (isset($item['value'])) : ?>
                                <?php echo $item['value'] ?>x
                            <?php endif; ?>
                            <?php if (isset($item['percent'])) : ?>
                                (<?php echo round($item['percent'], 1) ?>%)
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>