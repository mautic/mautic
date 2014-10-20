<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="media">
    <?php if (isset($activity['profileImage'])): ?>
        <div class="thumbnail pull-left">
            <img src="<?php echo  $activity['profileImage']; ?>" width="100px" class="media-object img-rounded" />
        </div>
    <?php endif; ?>
    <div class="media-body">
        <h4 class="media-title"><?php echo $activity['full_name']; ?></h4>
        <p><a href="https://instagram.com/<?php echo $activity['profileHandle']; ?>" target="_blank"><?php echo $activity['profileHandle']; ?></a></p>
        <p class="text-muted">
            <?php echo $activity['website']; ?>
        </p>
        <p class="text-muted">
            <?php echo $activity['bio']; ?>
        </p>
    </div>
</div>