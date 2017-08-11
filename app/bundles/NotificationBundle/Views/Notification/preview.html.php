<?php
/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
/* @var \Mautic\NotificationBundle\Entity\Notification $notification */
$url    = $notification->getUrl();
$button = $notification->getButton();
$icon = $notification->getNotificationIcon();

?>
<label>Preview</label>
<div id="notification-preview" class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="icon height-auto text-center">
                <img src="../../../<?php echo $icon ?>" />
            </div>
            <div class="text height-auto bg-white">
                <h4><?php echo $notification->getHeading()?></h4>
                <p><?php echo $notification->getMessage()?></p>
                <span><?php echo $_SERVER['HTTP_HOST']; ?></span>
            </div>
        </div>
        <?php if ($url && $button) : ?>
            <hr>
            <a href="<?php echo $url ?>"><?php echo $button ?></a>
        <?php endif; ?>
    </div>
</div>
