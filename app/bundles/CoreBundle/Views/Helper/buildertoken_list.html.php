<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="inline-token-list">
    <?php foreach ($tokens as $token => $description): ?>
    <a href="#" class="inline-token" data-token="<?php echo $token; ?>">
        <span><?php echo $description; ?></span> <span class="text-muted"><?php echo $token; ?></span>
    </a>
    <?php endforeach; ?>
</div>