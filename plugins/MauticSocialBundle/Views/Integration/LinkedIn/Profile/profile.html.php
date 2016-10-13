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
<div class="media">
    <?php if (isset($profile['profileImage'])): ?>
        <div class="thumbnail pull-left">
            <img src="<?php echo  $profile['profileImage']; ?>" width="100px" class="media-object img-rounded" />
        </div>
    <?php endif; ?>
    <div class="media-body">
        <h4 class="media-title"><?php echo $profile['formattedName']; ?></h4>
        <p><a href="https://www.linkedin.com/<?php echo $profile['profileHandle']; ?>" target="_blank"><?php echo $profile['profileHandle']; ?></a></p>
        <p class="text-muted">
            <?php echo $profile['headline']; ?>
        </p>
        <p class="text-muted">
            <?php echo $profile['summary']; ?>
        </p>
    </div>
</div>