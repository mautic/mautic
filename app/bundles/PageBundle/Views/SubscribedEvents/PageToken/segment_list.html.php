<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div id="contact-segments"> <div class="text-left"><?php echo  $view['form']->label($form['lead_lists']); ?></div>
<?php
$segmentNumber = count($form['lead_lists']->vars['choices']);
for ($i = ($segmentNumber - 1); $i >= 0; --$i): ?>
    <div id="segment-<?php echo $i; ?>" class="text-left">
        <?php echo $view['form']->widget($form['lead_lists'][$i]); ?>
        <?php echo $view['form']->label($form['lead_lists'][$i]); ?>
    </div>
    <?php
endfor;
unset($form['lead_lists']);
?>
</div>
