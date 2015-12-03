<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>

<div id="dashboard-modules">
<?php foreach ($modules as $module): ?>
    <div class="module pt-md col-md-<?php echo !empty($module->getWidth()) ? $module->getWidth() : 12 ?>" data-module-id="<?php echo $module->getId(); ?>">
        <?php echo $view->render('MauticDashboardBundle:Module:module.html.php', array(
            'module' => $module
        )); ?>
    </div>
<?php endforeach; ?>
<div class="clearfix"></div>
</div>
