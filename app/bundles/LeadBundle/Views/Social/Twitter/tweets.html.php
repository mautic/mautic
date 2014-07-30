<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<ul class="list-group">
     <?php foreach ($activity as $item): ?>
        <li class="list-group-item">
            <p><?php echo $item['tweet']; ?></p>
            <span class="text-muted"><?php echo date($dateFormats['datetime'], strtotime($item['published'])); ?></span>
        </li>
    <?php endforeach; ?>
</ul>
