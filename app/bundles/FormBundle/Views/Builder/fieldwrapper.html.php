<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\FormBundle\ConditionalField\Enum\ConditionalFieldEnum;

if (!isset($inBuilder)) {
    $inBuilder = false;
}

?>

<div class="<?php if (empty($isConditional)): ?>panel<?php endif; ?> form-field-wrapper"
     data-sortable-id="mauticform_<?php echo $field['id']; ?>">
    <?php if (!empty($isConditional)): ?>
    <div class="ml-20">
        <?php endif; ?>
        <?php
        echo $view->render(
            'MauticFormBundle:Builder:actions.html.php',
            [
                'id'             => $field['id'],
                'formId'         => $formId,
                'formName'       => '',
                'disallowDelete' => ('button' == $field['type']),
            ]
        );
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
            || !empty($field['conditions'])
        ): ?>
            <div class="ml-10">

                <?php if (!empty($field['conditions']['values'])): ?>
                    <span class="inline-spacer"">
                    <span style="text-transform: none"><?php echo $view['translator']->trans(
                            'mautic.form.field.form.condition.show.on'
                        ); ?></span>
                    <strong><?php echo $formFields[$field['parent']]['label']; ?></strong>

                    <span style="text-transform: none"><?php echo $view['translator']->trans(
                            'mautic.form.field.form.condition.select.value'
                        ); ?></span>
                    <strong><?php echo implode(', ', $field['conditions']['values']); ?></strong>
                    </span>
                    <br>
                <?php endif; ?>

                <?php if (!empty($field['leadField'])):
                    $icon = (in_array($field['leadField'], array_keys($companyFields))) ? 'building' : 'user';
                    ?>
                    <i class="fa fa-<?php echo $icon ?>" aria-hidden="true"></i>
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

        <?php if (isset($fields) && in_array($field['type'], ConditionalFieldEnum::getConditionalFieldTypes())): ?>
            <div class="row ml-15 mr-0 pb-15">
                <div class="mt-10 col-sm-6 col-xs-12">
                    <select class="chosen form-builder-new-component"
                            data-placeholder="<?php echo $view['translator']->trans(
                                'mautic.form.form.component.fields.conditional'
                            ); ?>">
                        <option value=""></option>
                        <?php foreach ($fields as $conditionalFieldType => $conditionalField): ?>
                            <?php if (!in_array($conditionalFieldType, $viewOnlyFields)): ?>
                                <option data-toggle="ajaxmodal"
                                        data-target="#formComponentModal"
                                        data-href="<?php echo $view['router']->path(
                                            'mautic_formfield_action',
                                            [
                                                'objectAction' => 'new',
                                                'type'         => $conditionalFieldType,
                                                'tmpl'         => 'field',
                                                'formId'       => $formId,
                                                'inBuilder'    => $inBuilder,
                                                'parent'       => $field['id'],
                                            ]
                                        ); ?>">
                                    <?php echo $conditionalField; ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($formFields as $field2):
            ?>
            <?php if (!empty($field2['parent']) && $field2['parent'] == $field['id']) : ?>
            <?php if (!empty($field2['isCustom'])):
                $params   = $field2['customParameters'];
                $template = $params['template'];
            else:
                $template = 'MauticFormBundle:Field:'.$field2['type'].'.html.php';
            endif; ?>
            <?php

            echo $view->render(
                'MauticFormBundle:Builder:fieldwrapper.html.php',
                [
                    'isConditional'     => true,
                    'template'          => $template,
                    'field'             => $field2,
                    'viewOnlyFields'    => $viewOnlyFields,
                    'inForm'            => true,
                    'id'                => $field2['id'],
                    'formId'            => $formId,
                    'contactFields'     => $contactFields,
                    'companyFields'     => $companyFields,
                    'inBuilder'         => $inBuilder,
                    'fields'            => $fields,
                    'formFields'        => $formFields,
                ]
            ); ?>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!empty($isConditional)): ?>
        <hr />
    </div>
<?php else: ?>
    <br>
<?php endif; ?>
</div>
