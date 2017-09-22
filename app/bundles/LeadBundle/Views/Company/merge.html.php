<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see         http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php

echo $view['form']->start($form);

?>

    <div class="hide">
        <?php echo $view['form']->row($form['buttons']); ?>
    </div>

<?php echo $view['form']->end($form); ?>
