<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$formName = '_'.$form->generateFormName().(isset($suffix) ? $suffix : '');
if (!isset($fields)) {
    $fields = $form->getFields();
}
$pageCount = 1;

if (!isset($inBuilder)) {
    $inBuilder = false;
}

if (!isset($action)) {
    $action = $view['router']->url('mautic_form_postresults', ['formId' => $form->getId()]);
}

if (!isset($theme)) {
    $theme = '';
}

if (!isset($contactFields)) {
    $contactFields = $companyFields = [];
}

if (!isset($style)) {
    $style = '';
}

if (!isset($isAjax)) {
    $isAjax = true;
}
?>

<?php echo $style; ?>

<div id="mauticform_wrapper<?php echo $formName ?>" class="mauticform_wrapper">
    <form autocomplete="false" role="form" method="post" action="<?php echo  $action; ?>" id="mauticform<?php echo $formName ?>" <?php if ($isAjax): ?> data-mautic-form="<?php echo ltrim($formName, '_') ?>"<?php endif; ?> enctype="multipart/form-data">
        <div class="mauticform-error" id="mauticform<?php echo $formName ?>_error"></div>
        <div class="mauticform-message" id="mauticform<?php echo $formName ?>_message"></div>
        <div class="mauticform-innerform">

            <?php
            /** @var \Mautic\FormBundle\Entity\Field $f */
            foreach ($fields as $fieldId => $f):
                if (isset($formPages['open'][$fieldId])):
                    // Start a new page
                    $lastFieldAttribute = ($lastFormPage === $fieldId) ? ' data-mautic-form-pagebreak-lastpage="true"' : '';
                    echo "\n          <div class=\"mauticform-page-wrapper mauticform-page-$pageCount\" data-mautic-form-page=\"$pageCount\"$lastFieldAttribute>\n";
                endif;

                if (!isset($submissions) || $f->showForContact($submissions, $lead, $form)):
                    if ($f->isCustom()):
                        if (!isset($fieldSettings[$f->getType()])):
                            continue;
                        endif;
                        $params = $fieldSettings[$f->getType()];
                        $f->setCustomParameters($params);

                        $template = $params['template'];
                    else:
                        if (!$f->getShowWhenValueExists() && $f->getLeadField() && $f->getIsAutoFill() && $lead && !empty($lead->getFieldValue($f->getLeadField()))) {
                            $f->setType('hidden');
                        }
                        $template = 'MauticFormBundle:Field:'.$f->getType().'.html.php';
                    endif;

                    echo $view->render(
                        $theme.$template,
                        [
                            'field'         => $f->convertToArray(),
                            'id'            => $f->getAlias(),
                            'formName'      => $formName,
                            'fieldPage'     => ($pageCount - 1), // current page,
                            'contactFields' => $contactFields,
                            'companyFields' => $companyFields,
                            'inBuilder'     => $inBuilder,
                        ]
                    );
                endif;

                if (isset($formPages) && isset($formPages['close'][$fieldId])):
                    // Close the page
                    echo "\n            </div>\n";
                    ++$pageCount;
                endif;

            endforeach;
            ?>
        </div>

        <input type="hidden" name="mauticform[formId]" id="mauticform<?php echo $formName ?>_id" value="<?php echo $form->getId(); ?>"/>
        <input type="hidden" name="mauticform[return]" id="mauticform<?php echo $formName ?>_return" value=""/>
        <input type="hidden" name="mauticform[formName]" id="mauticform<?php echo $formName ?>_name" value="<?php echo ltrim($formName, '_'); ?>"/>

        <?php echo (isset($formExtra)) ? $formExtra : ''; ?>
</form>
</div>
