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
<ul class="list-group">
	<?php
    $i     = 0;
    $total = count($activity); ?>

     <?php foreach ($activity as $item): ?>
     	<?php
         $border = 'bdr-b bdr-l-wdh-0 bdr-r-wdh-0';
         if ($i == 0 || $i == ($total - 1)):
             $border = 'bdr-w-0';
         endif;
         ?>
        <li class="<?php echo $border; ?> pa-15 list-group-item">
            <p><?php echo $item['tweet']; ?></p>
            <span class="text-muted"><i class="fa fa-clock-o"></i> <?php echo $view['date']->toFull($item['published']); ?></span>
        </li>
        <?php ++$i; ?>
    <?php endforeach; ?>
</ul>