<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticInstallBundle:Install:content.html.php');
}

$header = $view['translator']->trans('mautic.install.heading.stats.configuration');
$view['slots']->set("headerTitle", $header);
?>

<h2 class="page-header">
	<?php echo $header; ?>
</h2>
<p><?php echo $view['translator']->trans('mautic.install.stats.introtext'); ?></p>

<?php echo $view['form']->start($form); ?>

<div class="row mt-20">
    <div class="col-sm-6">
        <?php echo $view['form']->row($form['send_server_data']); ?>
    </div>
    <div class="col-sm-6 mt-20">
        <?php echo $view['form']->row($form['buttons']); ?>
    </div>
</div>

<?php echo $view['form']->end($form); ?>
