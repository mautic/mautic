<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'report');

$header = $view['translator']->trans('mautic.report.report.header.view', array('%name%' => $view['translator']->trans($report->getTitle())));

$view['slots']->set("headerTitle", $header);?>

<div class="scrollable">
    <?php // We need to dynamically create the table headers based on the result set ?>
    <?php if (count($result) > 0) : ?>
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <?php foreach ($result[0] as $key => $value) : ?>
                <th><?php echo ucfirst($key); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result as $row) : ?>
            <tr>
                <?php foreach ($row as $cell) : ?>
                <td><?php echo $cell; ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
    <?php echo $view['translator']->trans('mautic.report.report.noresults'); ?>
    <?php endif; ?>
    <div class="footer-margin"></div>
</div>
