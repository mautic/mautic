<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="row<?php echo $form->vars['attr']['class']; ?>" id="permissions-container">
    <div class="form-group col-lg-12 role-permissions">
        <label><?php echo $view['translator']->trans($form->vars['label']); ?></label>
        <?php echo $view['form']->errors($form) ?>
        <?php if (!empty($form->children)): ?>
            <?php foreach ($form->children as $child): ?>
                <?php echo $view['form']->row($child); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>