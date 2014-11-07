<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel panel-default report-list">
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
    <div class="table-responsive panel-collapse pull out">
        <?php // We need to dynamically create the table headers based on the result set ?>
        <?php if (count($graph['data']) > 0) : ?>
        <table class="table table-hover table-striped table-bordered report-list" id="reportTable">
            <thead>
                <tr>
                    <?php foreach ($graph['data'][0] as $key => $value) : ?>
                        <?php if ($key != 'id') : ?>
                            <th><?php echo ucfirst($key); ?></th>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($graph['data'] as $rowKey => $row) : ?>
                <tr>
                    <?php foreach ($row as $cellName => $cell) : ?>
                        <?php if (array_key_exists('id', $graph['data'][0]) && $cellName == 'title' && isset($graph['link'])) : ?>
                            <td>
                                <a href="<?php echo $view['router']->generate($graph['link'],
                                    array("objectAction" => "view", "objectId" => $row['id'])); ?>"
                                   data-toggle="ajax">
                                    <?php echo $cell; ?>
                                </a>
                            </td>
                        <?php elseif ($cellName != 'id') : ?>
                            <td><?php echo $view['assets']->makeLinks($cell); ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
        <?php endif; ?>
    </div>
</div>
