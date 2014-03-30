<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<?php foreach ($view['session']->getFlash('notice') as $message): ?>
<div class="alert alert-success alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <span><?php echo $message; ?></span>
</div>
<?php endforeach; ?>

<?php foreach ($view['session']->getFlash('warning') as $message): ?>
<div class="alert alert-warning alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <span><?php echo $message; ?></span>
</div>
<?php endforeach; ?>

<?php foreach ($view['session']->getFlash('error') as $message): ?>
<div class="alert alert-danger alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <span><?php echo $message; ?></span>
</div>
<?php endforeach; ?>