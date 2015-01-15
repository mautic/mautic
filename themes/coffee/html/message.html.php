<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend(":$template:base.html.php");
?>
<div class="well text-center">
    <h2><?php echo $message; ?></h2>
    <?php if (isset($content)): ?>
    <div class="text-left"><?php echo $content; ?></div>
    <?php endif; ?>
</div>