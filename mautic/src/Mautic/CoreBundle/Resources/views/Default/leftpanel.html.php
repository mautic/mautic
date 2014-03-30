<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="left-panel-inner-wrapper">
    <div class="left-panel-header">
        <img class="pull-left" src="<?php echo $view['assets']->getUrl('media/images/mautic_circle.png'); ?>" />
        <span>Mautic</span>
    </div>
    <div class="side-panel-nav-wrapper">
        <?php echo $view['knp_menu']->render('main', array("menu" => "main")); ?>
    </div>
</div>