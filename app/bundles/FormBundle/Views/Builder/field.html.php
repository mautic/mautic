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
        <?php foreach ($form->children as $child): ?>
        <?php if ($child->isRendered()) continue; ?>
        <?php if (!empty($child['text'])) continue; ?>
        <div class="col-md-6">
            <?php echo $view['form']->row($child); ?>
        </div>
        <?php endforeach; ?>

        <?php foreach ($properties as $name => $property): ?>
        <?php if ($name == 'test'): ?>
        <?php echo $view['form']->rowIfExists($properties, $name, '<div class="col-md-12">{content}</div>'); ?>
        <?php else: ?>
        <?php echo $view['form']->rowIfExists($properties, $name, $template); ?>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php echo $view['form']->end($form); ?>
</div>