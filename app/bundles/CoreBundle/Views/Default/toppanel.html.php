<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="page-header-section" id="breadcrumbs">
    <?php echo $view->render('MauticCoreBundle:Default:breadcrumbs.html.php'); ?>
</div>

<?php if ($view['slots']->has("actions")): ?>
<div class="page-header-section">
    <div class="toolbar">
        <?php $view['slots']->output('actions'); ?>
        <?php echo $view->render('MauticCoreBundle:Default:toolbar.html.php'); ?>
    </div>
</div>
<?php endif; ?>