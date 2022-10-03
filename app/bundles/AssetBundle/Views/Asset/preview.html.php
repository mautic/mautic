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

<h5 class="fw-sb mb-xs"><?php echo $view['translator']->trans('mautic.asset.asset.preview'); ?></h5>
<div class="text-center">
    <?php if ($activeAsset->isImage()): ?>
        <img src="<?php echo $assetDownloadUrl.'?stream=1'; ?>" alt="<?php echo $view->escape($activeAsset->getTitle()); ?>" class="img-thumbnail" />
    <?php elseif ('pdf' == strtolower($activeAsset->getFileType())) : ?>
        <iframe src="<?php echo $assetDownloadUrl.'?stream=1'; ?>#view=FitH" class="col-sm-12"></iframe>
    <?php elseif (0 === strpos($activeAsset->getMime(), 'video') || in_array($activeAsset->getExtension(), ['mpg', 'mpeg', 'mp4', 'webm'])): ?>
        <video src="<?php echo $assetDownloadUrl.'?stream=1'; ?>" controls>
            <?php echo $view['translator']->trans('mautic.asset.no_video_support'); ?>
        </video>
    <?php elseif (0 === strpos($activeAsset->getMime(), 'audio') || in_array($activeAsset->getExtension(), ['mp3', 'ogg', 'wav'])): ?>
        <audio controls>
            <source src="<?php echo $assetDownloadUrl.'?stream=1'; ?>" type="<?php echo $activeAsset->getMime(); ?>">
            <?php echo $view['translator']->trans('mautic.asset.no_audio_support'); ?>
        </audio>
    <?php else: ?>
        <i class="<?php echo $activeAsset->getIconClass(); ?> fa-5x"></i>
    <?php endif; ?>
</div>
<div class="clearfix"></div>
