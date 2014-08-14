<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
$view->extend('MauticAssetBundle:Asset:index.html.php');
?>

<div class="table-responsive scrollable body-white padding-sm asset-list">
    <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered asset-list">
            <thead>
            <tr>
                <th class="col-asset-actions"></th>
            </tr>
            </thead>
            <tbody>
            
            </tbody>
        </table>
    <?php else: ?>
        <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
    <?php endif; ?>
    <div class="footer-margin"></div>
</div>