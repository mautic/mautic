<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$attr = $form->vars['attr'];
?>

<?php foreach ($form->children as $child): ?>
    <div class="checkbox">
        <label>
            <?php echo $view['form']->widget($child, array('attr' => $attr)); ?>
            <?php echo $view['translator']->trans($child->vars['label']); ?>
        </label>
    </div>
<?php endforeach; ?>