<div class="panel<?php echo $form->vars['attr']['class']; ?>">
    <div class="panel-heading">
        <h4 class="panel-title">
            <a data-toggle="collapse" data-parent="#<?php echo $form->vars['attr']['data-parent']; ?>"
               href="#<?php echo $form->vars['attr']['id']; ?>">
                <?php echo $view['translator']->trans($form->vars['label']); ?>
            </a>
        </h4>
    </div>
    <div id="<?php echo $form->vars['attr']['id']; ?>" class="panel-collapse collapse in">
        <div class="panel-body">