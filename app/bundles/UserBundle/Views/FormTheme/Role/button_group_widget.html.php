<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//apply attributes to radios
$attr = $form->vars['attr'];
?>
<div class="btn-group btn-block" data-toggle="buttons">
    <?php foreach ($form as $child): ?>
        <?php
        $class = (!empty($child->vars['checked']) ? ' active' : '') .  (!empty($child->vars['disabled']) || !empty($child->vars['read_only']) ? ' disabled' : '');
        if (strpos($child->vars['cache_key'], '_role_permissions') !== false):
            $attr['data-permission'] = $form->vars['name'] . ':' . $child->vars['value'];
        endif;
        ?>
        <label class="btn btn-default<?php echo $class; ?>">
            <?php echo $view['form']->widget($child, array('attr' => $attr)); ?>
            <?php echo $view['translator']->trans($child->vars['label']); ?>
        </label>
    <?php endforeach; ?>
</div>