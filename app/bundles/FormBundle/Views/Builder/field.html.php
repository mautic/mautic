<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$template   = '<div class="col-md-6">{content}</div>';
$properties = (isset($form['properties'])) ? $form['properties'] : array();
?>

<div class="bundle-form">
    <div class="bundle-form-header">
        <h3><?php echo $fieldHeader; ?></h3>
    </div>

    <?php echo $view['form']->start($form); ?>

    <h4 class="mt-md mb-sm"><?php echo $view['translator']->trans('mautic.form.field.section.general'); ?></h4>
    <div class="row">
        <?php echo $view['form']->rowIfExists($form, 'label', $template); ?>
        <?php echo $view['form']->rowIfExists($form, 'showLabel', $template); ?>
        <?php echo $view['form']->rowIfExists($form, 'defaultValue', $template); ?>
        <?php echo $view['form']->rowIfExists($form, 'saveResult', $template); ?>
        <?php echo $view['form']->rowIfExists($form, 'helpMessage', $template); ?>
    </div>

    <?php if (isset($form['leadField'])): ?>
        <hr />
        <h4 class="mb-sm"><?php echo $view['translator']->trans('mautic.form.field.section.leadfield'); ?></h4>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form['leadField']); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($form['isRequired'])): ?>
    <hr />
    <h4 class="mb-sm"><?php echo $view['translator']->trans('mautic.form.field.section.validation'); ?></h4>
    <div class="row">
        <?php echo $view['form']->rowIfExists($form, 'validationMessage', $template); ?>
        <?php echo $view['form']->rowIfExists($form, 'isRequired', $template); ?>
    </div>
    <?php endif; ?>

    <?php if (isset($form['properties'])): ?>
    <hr />
    <h4 class="mb-sm"><?php echo $view['translator']->trans('mautic.form.field.section.properties'); ?></h4>
    <div class="row">
        <?php if (isset($properties['list']) && count($properties) === 1): ?>
        <div class="col-md-6">
            <?php echo $view['form']->row($form['properties']); ?>
        </div>
        <?php else: ?>

        <?php foreach ($properties as $name => $property): ?>
        <?php if ($form['properties'][$name]->isRendered() || $name == 'labelAttributes') continue; ?>
        <?php $col = ($name == 'text') ? 12 : 6; ?>
        <div class="col-md-<?php echo $col; ?>">
            <?php echo $view['form']->row($form['properties'][$name]); ?>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($form['labelAttributes']) || isset($form['inputAttributes']) || isset($form['containerAttributes']) || isset($properties['labelAttributes'])): ?>
        <hr />
        <h4 class="mb-sm"><?php echo $view['translator']->trans('mautic.form.field.section.attributes'); ?></h4>
        <div class="row">
            <?php echo $view['form']->rowIfExists($form, 'labelAttributes', $template); ?>
            <?php echo $view['form']->rowIfExists($form, 'inputAttributes', $template); ?>
            <?php echo $view['form']->rowIfExists($form, 'containerAttributes', $template); ?>
            <?php echo $view['form']->rowIfExists($properties, 'labelAttributes', $template); ?>
        </div>
    <?php endif; ?>

    <?php echo $view['form']->end($form); ?>
</div>