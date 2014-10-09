<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'formresult');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.form.result.header.index', array(
    '%name%' => $form->getName()
)));
?>

<?php $view['slots']->start("actions"); ?>
<a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
    'mautic_form_export', array("objectId" => $form->getId(), "format" => "csv")); ?>"
   data-toggle="download">
    <?php echo $view["translator"]->trans("mautic.form.result.export.csv"); ?>
</a>
<?php if (class_exists('PHPExcel')): ?>
<a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
    'mautic_form_export', array("objectId" => $form->getId(), "format" => "xlsx")); ?>"
   data-toggle="download">
    <?php echo $view["translator"]->trans("mautic.form.result.export.xlsx"); ?>
</a>
<?php endif; ?>
<a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
    'mautic_form_export', array("objectId" => $form->getId(), "format" => "html")); ?>"
   target="_blank">
    <?php echo $view["translator"]->trans("mautic.form.result.export.html"); ?>
</a>
<?php if (class_exists('mPDF')): ?>
<a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
    'mautic_form_export', array("objectId" => $form->getId(), "format" => "pdf")); ?>"
   target="_blank">
    <?php echo $view["translator"]->trans("mautic.form.result.export.pdf"); ?>
</a>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<?php $view['slots']->output('_content'); ?>