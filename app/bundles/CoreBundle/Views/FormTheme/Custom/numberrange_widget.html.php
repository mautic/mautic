<div class="input-group">
    <?php echo $view['form']->widget($form['number_from']); ?>
    <span class="input-group-addon" style="border-left: 0;border-right: 0;">
        <?php echo $view['form']->label($form['number_to']); ?>
    </span>
    <?php echo $view['form']->widget($form['number_to']); ?>
</div>
<?php if ($view['form']->containsErrors($form['number_from']) || $view['form']->containsErrors($form['number_to'])): ?>
<div class="row has-error">
    <div class="col-md-6">
        <?php echo $view['form']->errors($form['number_from']); ?>
    </div>
    <div class="col-md-6">
        <?php echo $view['form']->errors($form['number_to']); ?>
    </div>
</div>
<?php endif; ?>
