<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view['slots']->set('mauticContent', 'widget');
$userId = $form->vars['data']->getId();
if (!empty($userId)) {
    $header = $view['translator']->trans('mautic.dashboard.widget.header.edit');
} else {
    $header = $view['translator']->trans('mautic.dashboard.widget.header.new');
}
?>
<?php echo $view['form']->start($form); ?>

<div class="row form-group">
    <div class="col-xs-6">
        <?php echo $view['form']->label($form['name']); ?>
        <?php echo $view['form']->widget($form['name']); ?>
        <div class="has-error"><?php echo $view['form']->errors($form['name']); ?></div>
    </div>
    <div class="col-xs-6">
        <?php echo $view['form']->label($form['type']); ?>
        <?php echo $view['form']->widget($form['type']); ?>
        <div class="has-error"><?php echo $view['form']->errors($form['type']); ?></div>
    </div>
</div>
<div class="row form-group">
    <div class="col-xs-6">
        <?php echo $view['form']->label($form['width']); ?>
        <?php echo $view['form']->widget($form['width']); ?>
    </div>
    <div class="col-xs-6">
        <?php echo $view['form']->label($form['height']); ?>
        <?php echo $view['form']->widget($form['height']); ?>
    </div>
</div>

<?php echo $view['form']->row($form['buttons']); ?>
<?php echo $view['form']->end($form); ?>