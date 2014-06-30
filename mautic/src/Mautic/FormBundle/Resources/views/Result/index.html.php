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
$view["slots"]->set("headerTitle", $view['translator']->trans('mautic.form.result.header.index', array(
    '%name%' => $form->getName()
)));
?>

<?php $view["slots"]->start("actions"); ?>
<li>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_form_export', array("formId" => $form->getId(), "format" => "csv")); ?>"
       data-toggle="download">
        <?php echo $view["translator"]->trans("mautic.form.result.export.csv"); ?>
    </a>
</li>
<?php if (class_exists('PHPExcel')): ?>
<li>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_form_export', array("formId" => $form->getId(), "format" => "xlsx")); ?>"
       data-toggle="download">
        <?php echo $view["translator"]->trans("mautic.form.result.export.xlsx"); ?>
    </a>
</li>
<?php endif; ?>
<li>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_form_export', array("formId" => $form->getId(), "format" => "html")); ?>"
       target="_blank">
        <?php echo $view["translator"]->trans("mautic.form.result.export.html"); ?>
    </a>
</li>
<?php if (class_exists('mPDF')): ?>
<li>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_form_export', array("formId" => $form->getId(), "format" => "pdf")); ?>"
       target="_blank">
        <?php echo $view["translator"]->trans("mautic.form.result.export.pdf"); ?>
    </a>
</li>
<?php endif; ?>
<?php $view["slots"]->stop(); ?>

<div class="table-responsive scrollable body-white padding-sm formresults">
    <?php echo $view->render('MauticFormBundle:Result:list.html.php', array(
        'items'       => $items,
        'filters'     => $filters,
        'form'        => $form,
        'page'        => $page,
        'limit'       => $limit,
        'tmpl'        => $tmpl,
        'dateFormat'  => $dateFormat
    )); ?>
    <div class="footer-margin"></div>
</div>