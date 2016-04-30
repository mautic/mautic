<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<div class="bg-auto bg-dark-xs col-xs-12 pa-md mb-lg">
    <div class="panel">
        <div class="panel-body box-layout">
            <div class="col-xs-6 va-m">
                <h5 class="text-white dark-md fw-sb mb-xs pull-left">
                    <span class="fa fa-download"></span>
                    <?php echo $view['translator']->trans($graph['name']); ?>
                </h5>
                <?php if (isset($graph['datasets']) && count($graph['datasets']) > 1) : ?>
                    <div class="pull-left pl-20">
                        <?php foreach ($graph['datasets'] as $dataset) : ?>
                            <span class="label label-default" style="background:<?php echo $dataset['strokeColor']; ?>">
                                <?php $label = (isset($options['translate']) && $options['translate'] === false) ? $dataset['label'] : $view['translator']->trans($graph['name'] . '.' . $dataset['label']); ?>
                                <?php echo $label; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-xs-6 va-m">
                <?php //echo $view->render('MauticCoreBundle:Helper:graph_dateselect.html.php', array('callback' => 'updateReportGraph')); ?>
            </div>
        </div>
        <div class="pt-0 pl-15 pb-10 pr-15">
            <div>
                <canvas class="graph graph-line" id="<?php echo str_replace('.', '-', $graph['name']); ?>" height="300"></canvas>
            </div>
        </div>
        <div id="<?php echo str_replace('.', '-', $graph['name']); ?>-data" class="hide"><?php echo json_encode($graph); ?></div>
    </div>
</div>
