<div class="input-group">
    <span class="input-group-addon">
        <?php echo $view['form']->label($form['date_from']); ?>
    </span>
    <?php echo $view['form']->widget($form['date_from']); ?>
    <span class="input-group-addon" style="border-left: 0;border-right: 0;">
        <?php echo $view['form']->label($form['date_to']); ?>
    </span>
    <?php echo $view['form']->widget($form['date_to']); ?>

    <?php if (isset($form['apply'])) : ?>
    <span class="input-group-btn">
        <?php echo $view['form']->row($form['apply']); ?>
    </span>
    <?php endif; ?>
</div>
<?php if ($view['form']->containsErrors($form['date_from']) || $view['form']->containsErrors($form['date_to'])): ?>
<div class="row has-error">
    <div class="col-md-6">
        <?php echo $view['form']->errors($form['date_from']); ?>
    </div>
    <div class="col-md-6">
        <?php echo $view['form']->errors($form['date_to']); ?>
    </div>
</div>
<?php endif; ?>
