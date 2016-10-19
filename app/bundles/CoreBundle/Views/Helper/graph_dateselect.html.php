<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!isset($class)) {
    $class = '';
}
?>

<?php echo $view['form']->start($dateRangeForm, ['attr' => ['class' => 'form-filter '.$class, 'style' => 'max-width: 380px']]); ?>
    <div class="input-group">
        <span class="input-group-addon">
            <?php echo $view['form']->label($dateRangeForm['date_from']); ?>
        </span>
        <?php echo $view['form']->widget($dateRangeForm['date_from']); ?>
        <span class="input-group-addon" style="border-left: 0;border-right: 0;">
            <?php echo $view['form']->label($dateRangeForm['date_to']); ?>
        </span>
        <?php echo $view['form']->widget($dateRangeForm['date_to']); ?>
        <span class="input-group-btn">
            <?php echo $view['form']->row($dateRangeForm['apply']); ?>
        </span>
    </div>
<?php echo $view['form']->end($dateRangeForm); ?>
