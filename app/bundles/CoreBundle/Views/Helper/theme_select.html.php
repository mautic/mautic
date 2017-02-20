<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$codeMode   = 'mautic_code_mode';
$isCodeMode = ($active == $codeMode);
?>
<?php if ($themes) : ?>
<div class="row">
    <div class="col-md-3 theme-list">
        <div class="panel panel-default <?php echo $isCodeMode ? 'theme-selected' : ''; ?>">
            <div class="panel-body text-center">
                <h3><?php echo $view['translator']->trans('mautic.core.code.mode'); ?></h3>
                <div class="panel-body text-center" style="height: 250px">
                    <i class="fa fa-code fa-5x text-muted" aria-hidden="true" style="padding-top: 75px; color: #E4E4E4;"></i>
                </div>
                <a href="#" type="button" data-theme="<?php echo $codeMode; ?>" class="select-theme-link btn btn-default <?php echo $isCodeMode ? 'hide' : '' ?>">
                    Select
                </a>
                <button type="button" class="select-theme-selected btn btn-default <?php echo $isCodeMode ? '' : 'hide' ?>" disabled="disabled">
                    Selected
                </button>
            </div>
        </div>
    </div>
    <?php foreach ($themes as $themeKey => $themeInfo) : ?>
        <?php $isSelected = ($active === $themeKey); ?>
        <?php if (!empty($themeInfo['config']['onlyForBC']) && !$isSelected) {
    continue;
} ?>
        <?php if (isset($themeInfo['config']['features']) && !in_array($type, $themeInfo['config']['features'])) {
    continue;
} ?>
        <?php $thumbnailName = 'thumbnail.png'; ?>
        <?php $hasThumbnail = file_exists($themeInfo['dir'].'/thumbnail.png'); ?>
        <?php
        if (file_exists($themeInfo['dir'].'/thumbnail_'.$type.'.png')) {
            $thumbnailName = 'thumbnail_'.$type.'.png';
            $hasThumbnail = true;
        }
        ?>
        <?php $thumbnailUrl = $view['assets']->getUrl('themes/'.$themeKey.'/'.$thumbnailName); ?>
        <div class="col-md-3 theme-list">
            <div class="panel panel-default <?php echo $isSelected ? 'theme-selected' : ''; ?>">
                <div class="panel-body text-center">
                    <h3><?php echo $themeInfo['name']; ?></h3>
                    <?php if ($hasThumbnail) : ?>
                        <a href="#" data-toggle="modal" data-target="#theme-<?php echo $themeKey; ?>">
                            <div style="background-image: url(<?php echo $thumbnailUrl ?>);background-repeat:no-repeat;background-size:contain; background-position:center; width: 100%; height: 250px"></div>
                        </a>
                    <?php else : ?>
                        <div class="panel-body text-center" style="height: 250px">
                            <i class="fa fa-file-image-o fa-5x text-muted" aria-hidden="true" style="padding-top: 75px; color: #E4E4E4;"></i>
                        </div>
                    <?php endif; ?>
                    <a href="#" type="button" data-theme="<?php echo $themeKey; ?>" class="select-theme-link btn btn-default <?php echo $isSelected ? 'hide' : '' ?>">
                        Select
                    </a>
                    <button type="button" class="select-theme-selected btn btn-default <?php echo $isSelected ? '' : 'hide' ?>" disabled="disabled">
                        Selected
                    </button>
                </div>
            </div>
            <?php if ($hasThumbnail) : ?>
                <!-- Modal -->
                <div class="modal fade" id="theme-<?php echo $themeKey; ?>" tabindex="-1" role="dialog" aria-labelledby="<?php echo $themeKey; ?>">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="<?php echo $themeKey; ?>"><?php echo $themeInfo['name']; ?></h4>
                            </div>
                            <div class="modal-body">
                                <div style="background-image: url(<?php echo $thumbnailUrl ?>);background-repeat:no-repeat;background-size:contain; background-position:center; width: 100%; height: 600px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="clearfix"></div>
</div>
<?php endif; ?>
