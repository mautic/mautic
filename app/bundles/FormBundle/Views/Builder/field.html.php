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
    <div class="row">
        <?php echo $view['form']->rowIfExists($form, 'label', $template); ?>
        <?php echo $view['form']->rowIfExists($form, 'showLabel', $template); ?>
        <?php echo $view['form']->rowIfExists($properties, 'captcha', $template); ?>

    </div>
    <div class="row">
        <?php echo $view['form']->rowIfExists($form, 'validationMessage', $template); ?>
        <?php echo $view['form']->rowIfExists($form, 'isRequired', $template); ?>
    </div>

    <div class="row">
        <?php echo $view['form']->rowIfExists($form, 'defaultValue', $template); ?>
        <?php echo $view['form']->rowIfExists($form, 'helpMessage', $template); ?>
        <?php echo $view['form']->rowIfExists($properties, 'errorMessage', $template); ?>
    </div>
    <div class="row">
        <?php echo $view['form']->rowIfExists($form, 'labelAttributes', $template); ?>
        <?php echo $view['form']->rowIfExists($form, 'inputAttributes', $template); ?>
    </div>

    <div class="row">
        <?php foreach ($form->children as $childName => $child): ?>
        <?php if ($child->isRendered() || !empty($child['text']) || $childName == 'properties' || in_array('hidden', $child->vars['block_prefixes']) || $child->vars['id'] == 'formfield_buttons') continue; ?>
        <div class="col-md-6">
            <?php echo $view['form']->row($child); ?>
        </div>
        <?php endforeach; ?>

        <?php if (isset($properties['list']) && count($properties) === 1): ?>
        <div class="col-md-6">
            <?php echo $view['form']->row($form['properties']); ?>
        </div>
        <?php else: ?>

        <?php foreach ($properties as $name => $property): ?>
        <?php if ($form['properties'][$name]->isRendered()) continue; ?>
        <?php $col = ($name == 'text') ? 12 : 6; ?>
        <div class="col-md-<?php echo $col; ?>">
            <?php echo $view['form']->row($form['properties'][$name]); ?>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>
    <?php echo $view['form']->end($form); ?>
</div>