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

$title    = $notification->getHeading();
$url      = $notification->getUrl();
$message  = $notification->getMessage();
$icon     = $notificationUploader->getFullUrl($notification, 'icon');
$image    = $notificationUploader->getFullUrl($notification, 'image');

$actionButtonUrl1  = $notification->getActionButtonUrl1();
$actionButtonIcon1 = $notificationUploader->getFullUrl($notification, 'actionButtonIcon1');
$button            = $notification->getButton();

$actionButtonUrl2  = $notification->getActionButtonUrl2();
$actionButtonIcon2 = $notificationUploader->getFullUrl($notification, 'actionButtonIcon2');
$actionButtonText2 = $notification->getActionButtonText2();

?>
<div id="notification-preview" class="panel panel-default" style="max-width:400px; margin:0 auto;">
    <div class="panel-body">
        <?php if ($url): ?>
        <a target="_blank" href="<?php echo $url ?>">
        <div class="row">
            <div class="col-xs-4 icon height-auto text-center">

                <?php
                if ($icon) {
                    echo '<img src="'.$icon.'" alt="" style="width:100px; height:100px;">';
                } else {
                    echo '<span class="fa fa-bell fs-48"></span>';
                }
                ?>

            </div>
            <div class="col-xs-8 text height-auto bg-white">
                <h3>
                    <?php 
                    if ($notification->getHeading()) {
                        echo $notification->getHeading();
                    } else {
                        echo 'Your notification header';
                    }
                    ?>  
                </h3>
                <p>
                    <?php 
                    if ($notification->getMessage()) {
                        echo $notification->getMessage();
                    } else {
                        echo 'The message body of your notification';
                    }?>  
                </p>
                <small><?php echo $_SERVER['HTTP_HOST']; ?></small>
            </div>
        </div>
        <br>
        <?php
        if ($image) {
            ?>
        <div class="row">
            <div class="col-xs-12">
                <p><img src="<?php echo $image; ?>" style="width: 360px; height:240px" alt=""></p>
            </div>
        </div>
        <?php
        }
        ?>
            <?php
            echo '</a>';
            endif; ?>
        <?php if ($actionButtonUrl1 && $button) : ?>
            <p><a target="_blank" style="display:block; text-align:left;" class="btn btn-default" href="<?php echo $url ?>">
                    <?php
                    if ($actionButtonIcon1) {
                        echo '<img src="'.$actionButtonIcon1.'" alt="" style="width:16px; height:16px;">&nbsp; ';
                    }
                    ?>
                    <?php echo $button ?>
                </a>
            </p>
        <?php endif; ?>
        <?php if ($actionButtonUrl2 && $actionButtonText2) : ?>
            <p>
                <a target="_blank"  style="display:block; text-align:left;" class="btn btn-default" href="<?php echo $actionButtonUrl2 ?>">
                    <?php
                    if ($actionButtonIcon2) {
                        echo '<img src="'.$actionButtonIcon2.'" alt="" style="width:16px; height:16px;">&nbsp; ';
                    }
                    ?>
                    <?php echo $actionButtonText2 ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>
