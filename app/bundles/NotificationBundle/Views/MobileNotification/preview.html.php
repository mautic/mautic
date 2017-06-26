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
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <?php echo $notification->getHeading()?>
            <?php if ($url) : ?>
            <span class="pull-right">
                <a href="<?php echo $url; ?>" target="_blank"><span class="fa fa-external-link"></span></a>
            </span>
            <?php endif; ?>
        </h3>
    </div>
    <div class="panel-body">
        <p><?php echo $notification->getMessage()?></p>
        <?php if ($url && $button) : ?>
            <br>
            <p><a href="<?php echo $url ?>" class="btn btn-primary"><?php echo $button ?></a></p>
        <?php endif; ?>
    </div>
</div>
