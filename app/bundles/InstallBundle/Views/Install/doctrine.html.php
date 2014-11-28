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

$js = <<<JS
MauticInstaller.toggleBackupPrefix = function() {
    if (mQuery('#install_doctrine_step_backup_tables_0').prop('checked')) {
        mQuery('#backupPrefix').addClass('hide');
    } else {
        mQuery('#backupPrefix').removeClass('hide');
    }
};
JS;
$view['assets']->addScriptDeclaration($js);

$view['assets']->addScriptDeclaration("var test = {};");
?>

<h2 class="page-header">
	<?php echo $view['translator']->trans('mautic.install.heading.database.configuration'); ?>
</h2>
<p><?php echo $view['translator']->trans('mautic.install.database.introtext'); ?></p>


<?php echo $view['form']->start($form); ?>
<?php echo $view['form']->row($form['driver']); ?>

<div class="row">
    <div class="col-sm-6">
        <?php echo $view['form']->row($form['host']); ?>
    </div>
    <div class="col-sm-6">
        <?php echo $view['form']->row($form['port']); ?>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <?php echo $view['form']->row($form['name']); ?>
    </div>
    <div class="col-sm-6">
        <?php echo $view['form']->row($form['table_prefix']); ?>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <?php echo $view['form']->row($form['user']); ?>
    </div>
    <div class="col-sm-6">
        <?php echo $view['form']->row($form['password']); ?>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <?php echo $view['form']->row($form['backup_tables']); ?>
    </div>
    <?php $hide = (!$form['backup_tables']->vars['data']) ? ' hide' : ''; ?>
    <div class="col-sm-6<?php echo $hide; ?>" id="backupPrefix">
        <?php echo $view['form']->row($form['backup_prefix']); ?>
    </div>
</div>

<div class="row mt-20">
    <div class="col-sm-9">
        <div class="hide" id="waitMessage">
            <div class="alert alert-info">
                <strong><?php echo $view['translator']->trans('mautic.install.database.installing'); ?></strong>
            </div>
        </div>
    </div>
    <div class="col-sm-3">
        <?php echo $view['form']->row($form['buttons']); ?>
    </div>
</div>

<?php echo $view['form']->end($form); ?>