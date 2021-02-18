<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
foreach ($form->children as $child): ?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <?php echo $view['translator']->trans($child->vars['label']); ?>
        </h3>
    </div>
    <div class="panel-body">
        <?php $tooltip = $view['translator']->hasId($child->vars['label'].'.tooltip') ? $view['translator']->trans($child->vars['label'].'.tooltip') : ''; ?>
        <?php if ($tooltip): ?>
            <p><?php echo $tooltip; ?></p>
        <?php endif; ?>
        <?php echo $view['form']->widget($child); ?>
    </div>
</div>
<?php endforeach; ?>