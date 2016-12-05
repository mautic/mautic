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

<div class="offcanvas-main" id="OffCanvasMain">
    <?php if ($canvasContent): ?>
    <?php echo $view->render('MauticCoreBundle:RightPanel:content.html.php', ['canvasContent' => $canvasContent, 'canvas' => 'Main', 'hasLeft' => $hasLeft, 'hasRight' => $hasRight]); ?>
    <?php endif; ?>
</div>
<!--/ Offcanvas Main -->
