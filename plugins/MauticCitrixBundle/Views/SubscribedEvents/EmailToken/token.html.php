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
<link rel="stylesheet" href="<?php echo $view['assets']->getUrl('plugins/MauticCitrixBundle/Assets/css/citrix.css'); ?>" type="text/css"/>
<a class="citrix-start-button" href="<?php echo $link; ?>" target="_blank">
    <?php echo $text; ?>
</a>
<div style="clear:both"></div>
