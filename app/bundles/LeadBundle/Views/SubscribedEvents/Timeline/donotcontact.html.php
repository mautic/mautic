<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$dnc = $event['extra']['dnc'];
?>

<p><strong><?php echo $dnc['reason']; ?></strong></p>
<?php if (!empty($dnc['comments'])): ?>
<p class="small"><?php echo $dnc['comments']; ?></p>
<?php endif; ?>