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
        <?php
        $dataLink = (strpos($description, 'a:') === 0) ? ' data-link="true"' : '';
        if ($dataLink) $description = substr($description, 2);
        ?>
        <a href="#" class="inline-token" data-visual="<?php echo (in_array($token, $visualTokens)) ? 'true' : 'false'; ?>" data-token="<?php echo $token; ?>" data-description="<?php echo $description; ?>"<?php echo $dataLink; ?>>
            <span><?php echo $description; ?></span> <span class="text-muted"><?php echo $token; ?></span>
        </a>
    <?php endforeach; ?>
</div>