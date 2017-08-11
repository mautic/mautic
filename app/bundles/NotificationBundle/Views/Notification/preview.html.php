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

?>
<label>Preview</label>
<div id="notification-preview" class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="icon height-auto text-center">
                <span class="fa fa-bell fs-48"></span>
            </div>
            <div class="text height-auto bg-white">
                <h4><?php if ($notification->getHeading()) {echo $notification->getHeading();} else {echo 'Your notification header';}?></h4>
                <p><?php if ($notification->getMessage()) {echo $notification->getMessage();} else {echo 'The message body of your notification';}?></p>
                <span><?php echo $_SERVER['HTTP_HOST']; ?></span>
            </div>
        </div>
        <?php if ($url && $button) : ?>
            <hr>
            <a href="<?php echo $url ?>"><?php echo $button ?></a>
        <?php endif; ?>
    </div>
</div>
