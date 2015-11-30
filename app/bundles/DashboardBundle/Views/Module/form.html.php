<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view['slots']->set('mauticContent', 'module');
$userId = $form->vars['data']->getId();
if (!empty($userId)) {
    $header = $view['translator']->trans('mautic.lead.note.header.edit');
} else {
    $header = $view['translator']->trans('mautic.lead.note.header.new');
}
?>
<?php echo $view['form']->start($form); ?>

<div class="row">
    <div class="col-xs-6">
        <?php echo $view['form']->label($form['name']); ?>
        <?php echo $view['form']->widget($form['name']); ?>
    </div>
    <div class="col-xs-6">
        <?php echo $view['form']->label($form['type']); ?>
        <?php echo $view['form']->widget($form['type']); ?>
    </div>
</div>
<div class="row mt-lg">
    <div class="col-xs-4">
        <?php echo $view['form']->label($form['width']); ?>
        <?php echo $view['form']->widget($form['width']); ?>
    </div>
    <div class="col-xs-4">
        <?php echo $view['form']->label($form['height']); ?>
        <?php echo $view['form']->widget($form['height']); ?>
    </div>
    <div class="col-xs-4">
        <?php echo $view['form']->label($form['ordering']); ?>
        <?php echo $view['form']->widget($form['ordering']); ?>
    </div>
</div>

<?php echo $view['form']->row($form['buttons']); ?>
<?php echo $view['form']->end($form); ?>