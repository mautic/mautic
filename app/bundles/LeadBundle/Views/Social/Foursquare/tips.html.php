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
    <?php $i = 0; ?>
    <?php foreach ($activity as $item): ?>
        <li class="bdr-w-0 list-group-item">
            <h4 class="mt-10 mb-10 pb-10"><i class="fa fa-check-circle-o"></i> <?php echo $item['tipText']; ?></h4>
            <p class="alert alert-warning">
                <?php echo $item['venueName']; ?><br />
                <?php foreach ($item['venueLocation'] as $l): ?>
                <?php echo $l; ?><br />
                <?php endforeach; ?>
            </p>
            <p class="text-muted"><i class="fa fa-clock-o"></i> <?php echo $view['date']->toFull($item['createdAt'], 'UTC', 'U'); ?></p>
            <?php echo $i == 0 ? '' : '<hr />'; ?>
        </li>
        <?php $i++; ?>
    <?php endforeach; ?>
</ul>