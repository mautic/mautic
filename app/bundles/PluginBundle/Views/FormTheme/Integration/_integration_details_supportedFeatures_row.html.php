<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$attr      = $form->vars['attr'];
$builtin   = (isset($formSettings['builtin_features'])) ? $formSettings['builtin_features'] : [];
$showLabel = (count($builtin) !== count($form->children));
?>

<div class="row">
    <div class="col-sm-12">
        <?php if ($showLabel): ?>
            <h4 class="mb-sm"><?php echo $view['translator']->trans($form->vars['label']); ?></h4>
        <?php endif; ?>
        <?php if (!empty($formNotes['supported_features'])): ?>
            <div class="alert alert-<?php echo $formNotes['supported_features']['type']; ?>">
                <?php echo $view['translator']->trans($formNotes['supported_features']['note']); ?>
            </div>
        <?php endif; ?>
        <?php foreach ($form->children as $child): ?>
            <?php if (!in_array($child->vars['value'], $builtin)): ?>
            <div class="checkbox" >
                <label>
                    <?php echo $view['form']->widget($child, ['attr' => $attr]); ?>
                    <?php echo $view['translator']->trans($child->vars['label']); ?>
                </label>
            </div>
            <?php else: ?>
                <?php $child->isRendered(); ?>
                <input type="hidden" id="<?php echo $child->vars['id']; ?>" name="<?php echo $child->vars['full_name']; ?>" value="<?php echo $view->escape($child->vars['value']); ?>" />
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
