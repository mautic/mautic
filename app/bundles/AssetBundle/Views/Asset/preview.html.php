<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$assetUrl = $baseUrl . $asset->getWebPath();
?>

<h5 class="fw-sb mb-xs"><?php echo $view['translator']->trans('mautic.asset.asset.preview'); ?></h5>
<div class="text-center">
    <?php if ($asset->isImage()) : ?>
        <img src="<?php echo $assetUrl; ?>" alt="<?php echo $asset->getTitle(); ?>" class="img-thumbnail" />
    <?php elseif (strtolower($asset->getFileType()) == 'pdf') : ?>
        <iframe src="<?php echo $assetUrl; ?>#view=FitH" class="col-sm-12"></iframe>
    <?php else : ?>
        <i class="<?php echo $asset->getIconClass(); ?> fa-5x"></i>
    <?php endif; ?>
</div>
