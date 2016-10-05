<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php echo $view['form']->start($form); ?>

<div class="row">
    <div class="col-md-12"><?php echo $view['form']->row($form['channels']); ?></div>
</div>
<div class="row">
    <div class="col-md-12"><?php echo $view['form']->row($form['frequency_number']); ?></div>
    <div class="col-md-12"><?php echo $view['form']->row($form['frequency_time']); ?></div>
</div>
<?php echo $view['form']->end($form); ?>

