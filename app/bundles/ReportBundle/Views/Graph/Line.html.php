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
                <div class="dropdown pull-right">
                    <button id="time-scopes" class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                        <span class="button-label"><?php echo $view['translator']->trans('mautic.asset.asset.downloads.daily'); ?></span>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" role="menu" aria-labelledby="time-scopes">
                        <li role="presentation">
                            <a href="#" onclick="Mautic.updateReportGraph(this, {'amount': 24, 'unit': 'H', 'graphName': '<?php echo $graph['name']; ?>'});return false;" role="menuitem" tabindex="-1">
                                <?php echo $view['translator']->trans('mautic.asset.asset.downloads.hourly'); ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#" class="bg-primary" onclick="Mautic.updateReportGraph(this, {'amount': 30, 'unit': 'D', 'graphName': '<?php echo $graph['name']; ?>'});return false;" role="menuitem" tabindex="-1">
                                <?php echo $view['translator']->trans('mautic.asset.asset.downloads.daily'); ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#" onclick="Mautic.updateReportGraph(this, {'amount': 20, 'unit': 'W', 'graphName': '<?php echo $graph['name']; ?>'});return false;" role="menuitem" tabindex="-1">
                                <?php echo $view['translator']->trans('mautic.asset.asset.downloads.weekly'); ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#" onclick="Mautic.updateReportGraph(this, {'amount': 24, 'unit': 'M', 'graphName': '<?php echo $graph['name']; ?>'});return false;" role="menuitem" tabindex="-1">
                                <?php echo $view['translator']->trans('mautic.asset.asset.downloads.monthly'); ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#" onclick="Mautic.updateReportGraph(this, {'amount': 10, 'unit': 'Y', 'graphName': '<?php echo $graph['name']; ?>'});return false;" role="menuitem" tabindex="-1">
                                <?php echo $view['translator']->trans('mautic.asset.asset.downloads.yearly'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
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
