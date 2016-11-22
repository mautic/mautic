<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$prodName = (isset($product)) ? $product : 'product';
$link     = (isset($productLink)) ? $productLink : '#';
$text     = (isset($productText)) ? $productText : 'Start GoTo'.ucfirst($prodName);
?>
<a href="<?php echo $link; ?>" target="_blank" style="font-size: 16px; color: #ffffff; text-decoration: none; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 18px; background-color: #4e5e9e; display: inline-block;">
    <?php echo $text; ?>
</a>
<div style="clear:both"></div>
