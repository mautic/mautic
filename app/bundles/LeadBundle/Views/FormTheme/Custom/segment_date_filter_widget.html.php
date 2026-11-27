<div>
    <?php $dateTypeMode = $form->children['dateTypeMode']; ?>
    <?php echo $view['form']->row($dateTypeMode); ?>
    <div<?php echo ('absolute' != $dateTypeMode->vars['value']) ? ' class="absolute-date hide"' : ' class="absolute-date"'; ?>>
        <?php echo $view['form']->row($form->children['absoluteDate']); ?>
    </div>

    <div<?php echo ('relative' != $dateTypeMode->vars['value']) ? ' class="relative-date hide"' : ' class="relative-date"'; ?>>
        <div class="row">
            <div class="col-sm-4">
                <?php echo $view['form']->row($form->children['relativeDateInterval']); ?>
            </div>
            <div class="col-sm-8">
                <?php echo $view['form']->row($form->children['relativeDateIntervalUnit']); ?>
            </div>
        </div>
    </div>
</div>