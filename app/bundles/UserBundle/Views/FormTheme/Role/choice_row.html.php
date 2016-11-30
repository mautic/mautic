<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$attr = $form->vars['attr'];
?>

<div class="row">
    <div class="form-group col-xs-12">
        <?php echo $view['form']->label($form, $label) ?>
        <div class="choice-wrapper">
            <?php foreach ($form->children as $child): ?>
                <div class="checkbox">
                    <label>
                        <?php
                        $attr['data-permission'] = $form->vars['name'].':'.$child->vars['value'];
                        echo $view['form']->widget($child, ['attr' => $attr]);
                        echo $view['translator']->trans($child->vars['label']);
                        ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
