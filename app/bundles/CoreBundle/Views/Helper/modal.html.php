<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$size            = (empty($size)) ? '' : ' modal-'.$size;
$class           = (!empty($class)) ? " $class" : '';
$body            = (empty($body)) ? '' : $body;
$footer          = (empty($footer)) ? '' : $footer;
$hidePlaceholder = (empty($body)) ? '' : ' hide';
$header          = (!isset($header)) ? '' : $header;
$padding         = (empty($padding)) ? '' : $padding;
?>

<div class="modal fade" id="<?php echo $id; ?>" tabindex="-1" role="dialog" aria-labelledby="<?php echo $id; ?>-label" aria-hidden="true">
    <div class="modal-dialog<?php echo $size; ?>">
        <div class="modal-content<?php echo $class; ?>">
            <?php if ($header !== false): ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span aria-hidden="true">&times;</span></button>

                <h4 class="modal-title" id="<?php echo $id; ?>-label">
                    <?php echo $header; ?>
                </h4>

                <!-- start: loading bar -->
                <div class="modal-loading-bar">
                    <?php echo $view['translator']->trans('mautic.core.loading'); ?>
                </div>
                <!--/ end: loading bar -->

            </div>
            <?php endif; ?>
            <div class="modal-body <?php echo $padding; ?>">
                <div class="loading-placeholder<?php echo $hidePlaceholder; ?>">
                    <?php echo $view['translator']->trans('mautic.core.loading'); ?>
                </div>
                <div class="modal-body-content">
                    <?php echo $body; ?>
                </div>
            </div>
            <?php if (!empty($footer) || !empty($footerButtons)) : ?>
            <div class="modal-footer">
                <?php if (!empty($footerButtons)): ?>
                <div class="modal-form-buttons"></div>
                <?php else: ?>
                <?php echo $footer; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
