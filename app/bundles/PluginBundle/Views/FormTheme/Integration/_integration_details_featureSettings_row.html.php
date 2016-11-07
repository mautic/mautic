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

<div class="row">
    <div class="col-sm-12">
        <h4 class="mb-sm mt-lg">
            <?php echo $view['translator']->trans($form->vars['label']); ?>
        </h4>
        <?php if (!empty($formNotes['features'])): ?>
            <div class="alert alert-<?php echo $formNotes['features']['type']; ?>">
                <?php echo $view['translator']->trans($formNotes['features']['note']); ?>
            </div>
        <?php endif; ?>

        <?php echo $view['form']->widget($form); ?>
    </div>
</div>
