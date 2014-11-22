<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<div class="page-header-block" id="flashes">
    <?php foreach ($view['session']->getFlash('notice') as $message): ?>
    <div class="alert alert-growl alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <span><?php echo $message; ?></span>
    </div>
    <?php endforeach; ?>

    <?php foreach ($view['session']->getFlash('warning') as $message): ?>
    <div class="alert alert-growl alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <span><?php echo $message; ?></span>
    </div>
    <?php endforeach; ?>

    <?php foreach ($view['session']->getFlash('error') as $message): ?>
    <div class="alert alert-growl alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <span><?php echo $message; ?></span>
    </div>
    <?php endforeach; ?>
</div>