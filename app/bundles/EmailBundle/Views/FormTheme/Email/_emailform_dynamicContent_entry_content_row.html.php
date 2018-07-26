<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="row form-group">
    <div class="col-xs-10">
        <?php echo $view['form']->label($form); ?>
    </div>
    <div class="col-xs-2">
        <?php if ('emailform_dynamicContent_0_content' !== $id) : ?>
            <a class="remove-item btn btn-default text-danger"><i class="fa fa-trash-o"></i></a>
        <?php endif ?>
    </div>
</div>
<div class="row form-group">
    <div class="col-xs-12">
        <?php echo $view['form']->widget($form); ?>
    </div>
</div>
