<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$pinned = ($app->getSession()->get("left-panel", 'default') == 'unpinned') ? ' unpinned' : '';
?>

<div class="left-panel-inner-wrapper">
    <div class="side-bar-pin left-side-bar-pin">
        <i class="fa fa-thumb-tack<?php echo $pinned; ?>" onclick="Mautic.stickSidePanel('left');"></i>
    </div>
    <div class="left-panel-header">
        <img class="pull-left" src="<?php echo $view['assets']->getUrl('assets/images/mautic_circle.png'); ?>" />
        <span>Mautic</span>
    </div>
    <div class="side-panel-nav-wrapper">
        <nav>
            <?php echo $view['knp_menu']->render('main', array("menu" => "main")); ?>
        </nav>
    </div>
</div>