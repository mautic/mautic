<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div <?php echo $view['form']->block($form, 'widget_container_attributes') ?> class="<?php echo $containerClass; ?>">
    <?php if (!$form->parent && $errors): ?>
        <div class="has-error">
            <?php echo $view['form']->errors($form) ?>
        </div>
    <?php endif ?>
    <?php echo $view['form']->block($form, 'form_rows') ?>
    <?php echo $view['form']->rest($form) ?>
</div>