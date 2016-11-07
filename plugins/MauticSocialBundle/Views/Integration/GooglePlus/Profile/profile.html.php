<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$tableFields = ['gender', 'birthday', 'occupation', 'skills', 'braggingRights'];
?>
<div class="media">
    <?php if (isset($profile['profileImage'])): ?>
    <div class="pull-left thumbnail">
        <img src="<?php echo $profile['profileImage']; ?>" width="100px" class="media-object img-rounded" />
    </div>
    <?php endif; ?>

    <div class="media-body">
        <h4 class="media-heading"><?php echo $profile['displayName']; ?></h4>
        <p class="text-muted"><a href="https://plus.google.com/<?php echo $profile['profileHandle']; ?>" target="_blank"><?php echo $profile['profileHandle']; ?></a></p>
        <?php if (!empty($profile['aboutMe'])): ?>
        <p class="text-muted"><?php echo $profile['aboutMe']; ?></p>
        <?php endif; ?>
        <table class="table table-condensed table-bordered">
            <?php foreach ($tableFields as $t): ?>
            <?php if (!empty($profile[$t])): ?>
            <tr>
                <td><?php echo $view['translator']->transConditional("mautic.integration.common.{$t}", "mautic.integration.GooglePlus.{$t}"); ?></td>
                <td><?php echo $profile[$t]; ?></td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </div>
</div>