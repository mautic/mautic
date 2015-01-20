<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'integration');

$header = $view['translator']->trans('mautic.addon.integration.header.index');
if ($addonFilter) {
    $filterValue = $addonFilter['id'];
    $header     .= ' - ' . $addonFilter['name'];
} else {
    $filterValue = '';
}
$view['slots']->set('headerTitle', $header);
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <div class="panel-body">
        <div class="box-layout">
            <div class="row">
                <div class="col-xs-3 va-m">
                    <select id="integrationFilter" onchange="Mautic.filterIntegrations(true);" class="form-control" data-placeholder="<?php echo $view['translator']->trans('mautic.integration.filter.all'); ?>">
                        <option value=""></option>
                        <?php foreach ($addons as $a): ?>
                        <option<?php echo ($filterValue === $a['id']) ? ' selected' : ''; ?> value="<?php echo $a['id']; ?>"><?php echo $a['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
