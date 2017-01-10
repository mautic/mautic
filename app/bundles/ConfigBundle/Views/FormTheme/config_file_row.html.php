<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$hasErrors     = count($form->vars['errors']);
$feedbackClass = (!empty($hasErrors)) ? ' has-error' : '';
$field         = $form->vars['name'];
$hide          = (!empty($fieldValue)) ? '' : ' hide';
$filename      = \Mautic\CoreBundle\Helper\InputHelper::alphanum($view['translator']->trans($form->vars['label']), true, '_');
$downloadUrl   = $view['router']->path('mautic_config_action',
    [
        'objectAction' => 'download',
        'objectId'     => $field,
        'filename'     => $filename,
    ]
);
$removeUrl = $view['router']->path('mautic_config_action',
    [
        'objectAction' => 'remove',
        'objectId'     => $field,
    ]
);
?>
<div class="row">
    <div class="form-group col-xs-12 <?php echo $feedbackClass ?>">
        <?php echo $view['form']->label($form, $form->vars['label']) ?>
        <span class="small pull-right<?php echo $hide; ?>">
            <a
               data-toggle="confirmation"
               href="<?php echo $removeUrl ?>"
               data-message="<?php echo $view->escape($view['translator']->trans('mautic.config.remove_file_contents')) ?>"
               data-confirm-text="<?php echo $view->escape($view['translator']->trans('mautic.core.remove')) ?>"
               data-confirm-callback="removeConfigValue"
               data-cancel-text="<?php echo $view->escape($view['translator']->trans('mautic.core.form.cancel')); ?>">
                <?php echo $view['translator']->trans('mautic.core.remove') ?>
            </a>
            <span> | </span>
            <a href="<?php echo $downloadUrl ?>"><?php echo $view['translator']->trans('mautic.core.download') ?></a>
        </span>
        <?php echo $view['form']->widget($form) ?>
        <?php echo $view['form']->errors($form) ?>
    </div>
</div>