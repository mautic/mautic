<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!isset($inBuilder)) {
    $inBuilder = false;
}

?>
<div class="panel form-field-wrapper" data-sortable-id="mauticform_<?php echo $field['id']; ?>">
    <?php
    echo $view->render('MauticFormBundle:Builder:actions.html.php',
        [
            'id'             => $field['id'],
            'formId'         => $formId,
            'formName'       => '',
            'disallowDelete' => ('button' == $field['type']),
        ]);
    ?>
    <div class="row ml-0 mr-0"><?php // wrap in a row to keep bootstrap container classes from affecting builder layout?>
    <?php echo $view->render(
        $template,
        [
            'field'         => $field,
            'inForm'        => true,
            'id'            => $field['id'],
            'formId'        => $formId,
            'contactFields' => (isset($contactFields)) ? $contactFields : [],
            'companyFields' => (isset($companyFields)) ? $companyFields : [],
            'inBuilder'     => $inBuilder,
        ]
    );
    ?>
    </div>
    <?php if ((isset($field['showWhenValueExists']) && $field['showWhenValueExists'] === false) || !empty($field['showAfterXSubmissions'])
        || !empty($field['leadField'])
    ): ?>
        <div class="panel-footer">
            <?php if (!empty($field['leadField'])):
                $icon = (in_array($field['leadField'], array_keys($companyFields))) ? 'building' : 'user';
                ?>
                <i class="fa fa-<?php echo $icon?>" aria-hidden="true"></i>
                <span class="inline-spacer">
            <?php
                if (isset($contactFields[$field['leadField']]['label'])) {
                    echo $contactFields[$field['leadField']]['label'];
                } elseif ($companyFields[$field['leadField']]['label']) {
                    echo $companyFields[$field['leadField']]['label'];
                } else {
                    ucfirst($field['leadField']);
                }
            ?>
        </span>
            <?php endif; ?>
            <?php if (isset($field['showWhenValueExists']) && $field['showWhenValueExists'] === false): ?>
                <i class="fa fa-eye-slash" aria-hidden="true"></i>
                <span class="inline-spacer">
            <?php echo $view['translator']->trans('mautic.form.field.hide.if.value'); ?>
        </span>
            <?php endif; ?>
            <?php if (!empty($field['showAfterXSubmissions'])): ?>
                <i class="fa fa-refresh" aria-hidden="true"></i>
                <span class="inline-spacer">
            <?php echo $view['translator']->transChoice(
                'mautic.form.field.hide.if.submission.count',
                (int) $field['showAfterXSubmissions'],
                ['%count%' => (int) $field['showAfterXSubmissions']]
            ); ?>
        </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
