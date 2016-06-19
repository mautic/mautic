<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'formresult');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.form.result.header.index', array(
    '%name%' => $form->getName()
)));

$buttons = array();

$buttons[] = array(
    'attr' => array(
        'target' => '_new',
        'data-toggle' => '',
        'class'       => 'btn btn-default btn-nospin',
        'href'   => $view['router']->path('mautic_form_export', array('objectId' => $form->getId(), 'format' => 'html'))
    ),
    'btnText' => $view['translator']->trans('mautic.form.result.export.html'),
    'iconClass' => 'fa fa-file-code-o'
);

$buttons[] = array(
    'attr' => array(
        'data-toggle' => 'download',
        'data-toggle' => '',
        'class'       => 'btn btn-default btn-nospin',
        'href'        => $view['router']->path('mautic_form_export', array('objectId' => $form->getId(), 'format' => 'csv'))
    ),
    'btnText' => $view['translator']->trans('mautic.form.result.export.csv'),
    'iconClass' => 'fa fa-file-text-o'
);

if (class_exists('PHPExcel')) {
    $buttons[] = array(
        'attr' => array(
            'data-toggle' => 'download',
            'data-toggle' => '',
            'class'       => 'btn btn-default btn-nospin',
            'href'        => $view['router']->path('mautic_form_export', array('objectId' => $form->getId(), 'format' => 'xlsx'))
        ),
        'btnText' => $view['translator']->trans('mautic.form.result.export.xlsx'),
        'iconClass' => 'fa fa-file-excel-o'
    );
}

$buttons[] = array(
    'confirm' => array (
        'message'         => $view['translator']->trans('mautic.form.results.form.confirmbatchdelete'),
        'confirmText'     => $view['translator']->trans('mautic.core.form.delete'),
        'confirmAction'   => $view['router']->path('mautic_form_results_delete', array_merge(array('formId' => $form->getId()))),
        'confirmCallback' => 'executeBatchAction',
        'iconClass'       => 'fa fa-trash-o text-danger',
        'btnText'         => $view['translator']->trans('mautic.core.form.delete'),
        'tooltip'         => $view['translator']->trans('mautic.core.form.tooltip.bulkdelete'),
        'precheck'        => 'batchActionPrecheck'
    )
);
$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array('customButtons' => $buttons)));
?>

<div class="page-list">
    <?php $view['slots']->output('_content'); ?>
</div>
