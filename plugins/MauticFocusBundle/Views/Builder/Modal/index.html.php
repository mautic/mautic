<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$props     = $focus['properties'];
$style     = $focus['style'];
$placement = (isset($props[$style]['placement'])) ? str_replace('_', '-', $props[$style]['placement']) : false;
$animate   = (!empty($preview) && !empty($props['animate'])) ? ' mf-animate' : '';
?>
    <style scoped>
        .mf-<?php echo $style; ?> {
            border-color: #<?php echo $props['colors']['primary']; ?>
        }
    </style>
    <div class="mautic-focus mf-<?php echo $style; ?><?php if ($placement) {
    echo " mf-$style-$placement";
} ?><?php echo $animate; ?>">
        <div class="mf-<?php echo $style; ?>-container">
            <div class="mf-<?php echo $style; ?>-close">
                <a href="javascript:void(0)"<?php if (!empty($preview)): echo ' onclick="Mautic.closeFocusModal(\''.$style.'\')"'; endif; ?>>x</a>
            </div>
            <div class="mf-content">
                <?php if (in_array($htmlMode, ['editor', 'html'])): ?>
                    <?php echo html_entity_decode($focus[$htmlMode]); ?>
                <?php else: ?>
                <div class="mf-headline"><?php echo $props['content']['headline']; ?></div>
                <?php if ($props['content']['tagline']): ?>
                    <div class="mf-tagline"><?php echo $props['content']['tagline']; ?></div>
                <?php endif; ?>
                <div class="mf-inner-container">
                    <?php if ($focus['type'] == 'form' && !empty($form)): ?>
                        {focus_form}
                    <?php elseif ($focus['type'] == 'link'): ?>
                        <a href="<?php echo (empty($preview)) ? $clickUrl
                            : '#'; ?>" class="mf-link" target="<?php echo ($props['content']['link_new_window']) ? '_new' : '_parent'; ?>">
                            <?php echo $props['content']['link_text']; ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php if ($style == 'modal'): ?>
    <div class="mf-move-to-parent mf-<?php echo $style; ?>-overlay mf-<?php echo $style; ?>-overlay-<?php echo $focus['id']; ?>"></div>
<?php endif; ?>