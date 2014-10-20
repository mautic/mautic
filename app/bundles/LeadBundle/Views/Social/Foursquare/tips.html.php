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
            <blockquote><?php echo $item['tipText']; ?></blockquote>
            <p class="text-muted">
                <?php echo $item['venueName']; ?><br />
                <?php foreach ($item['venueLocation'] as $l): ?>
                <?php echo $l; ?><br />
                <?php endforeach; ?>
            </p>
            <p class="text-muted"><?php echo $view['date']->toFull($item['createdAt'], 'UTC', 'U'); ?></p>
        </li>
    <?php endforeach; ?>
</ul>