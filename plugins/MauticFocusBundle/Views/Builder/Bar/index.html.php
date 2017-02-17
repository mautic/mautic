<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$props   = $focus['properties'];
$color   = \MauticPlugin\MauticFocusBundle\Model\FocusModel::isLightColor($props['colors']['primary']) ? '000000' : 'ffffff';
$animate = (!empty($preview) && !empty($props['animate'])) ? ' mf-animate' : '';
?>
<div class="mautic-focus mf-bar mf-bar-<?php echo $props['bar']['size']; ?> mf-bar-<?php echo $props['bar']['placement']; ?><?php if ($props['bar']['sticky']) {
    echo ' mf-bar-sticky';
} ?><?php echo $animate; ?>" style="background-color: #<?php echo $props['colors']['primary']; ?>;">

    <div class="mf-content">
        <?php if ($focus['html_mode']): ?>
            <?php echo html_entity_decode($focus['html']); ?>
        <?php else: ?>
        <div class="mf-headline"><?php echo $props['content']['headline']; ?></div>
        <?php if ($focus['type'] == 'form' && !empty($form)): ?>
            <?php echo $view->render(
                'MauticFocusBundle:Builder:form.html.php',
                ['form' => $form, 'style' => $focus['style'], 'focusId' => $focus['id'], 'preview' => $preview]
            ); ?>
        <?php elseif ($focus['type'] == 'link'): ?>
            <a href="<?php echo (empty($preview)) ? $clickUrl : '#'; ?>" class="mf-link" target="<?php echo ($props['content']['link_new_window'])
                ? '_new' : '_parent'; ?>">
                <?php echo $props['content']['link_text']; ?>
            </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="mf-bar-collapse"></div>
</div>
<?php if ($props['bar']['allow_hide']): ?>
    <div class="mf-copy-to-parent mf-bar-collapser mf-bar-collapser-<?php echo $props['bar']['placement']; ?> mf-bar-collapser-<?php echo $props['bar']['size']; ?><?php if ($props['bar']['sticky']) {
                    echo ' mf-bar-collapser-sticky';
                } ?> mf-bar-collapser-<?php echo $focus['id']; ?>" style="background-color: #<?php echo $props['colors']['primary']; ?>; color: #<?php echo $props['colors']['text']; ?>;">
        <style scoped>
            .mf-bar-collapser-icon {
                color: #<?php  echo $color; ?>;
            }

            .mf-bar-collapser-icon:hover {
                color: #<?php  echo $color; ?>;
            }
        </style>
        <a class="mf-bar-collapser-icon" href="javascript:void(0)"<?php if (!empty($preview)) {
                    echo ' onclick="Mautic.toggleBarCollapse()"';
                } ?>>
            <?php $size          = ($props['bar']['size'] == 'large') ? 40 : 24; ?>
            <?php $transformSize = ($props['bar']['size'] == 'large') ? 20 : 20; ?>
            <?php $scale         = ($props['bar']['size'] == 'large') ? 1 : 0.6; ?>
            <?php $direction     = ($props['bar']['placement'] == 'top') ? '-90' : '90'; ?>
            <svg style="overflow: hidden;" xmlns="http://www.w3.org/2000/svg" width="<?php echo $size; ?>" version="1.1"
                 height="<?php echo $size; ?>" data-transform-size="<?php echo $transformSize; ?>" data-transform-direction="<?php echo $direction; ?>" data-transform-scale="<?php echo $scale; ?>">
                <g transform="scale(<?php echo $scale; ?>) rotate(<?php echo $direction; ?> <?php echo $transformSize; ?> <?php echo $transformSize; ?>)">
                    <desc>Created with Raphaël 2.1.2</desc>
                    <defs>
                        <?php $color = \MauticPlugin\MauticFocusBundle\Model\FocusModel::isLightColor($props['colors']['primary']) ? '000000'
                            : 'ffffff'; ?>
                        <linearGradient gradientTransform="matrix(1,0,0,1,-4,-4)" y2="0" x2="6.123233995736766e-17" y1="1" x1="0"
                                        id="1390-_0050af-_002c62">
                            <stop stop-color="#<?php echo $color; ?>" offset="0%"></stop>
                            <stop stop-color="#<?php echo $color; ?>" offset="100%"></stop>
                        </linearGradient>
                    </defs>
                    <path transform="matrix(1,0,0,1,4,4)" opacity="0" stroke-linejoin="round" stroke-width="3"
                          d="M16,1.466C7.973,1.466,1.466,7.973,1.466,16C1.466,24.027,7.973,30.534,16,30.534C24.027,30.534,30.534,24.027,30.534,15.999999999999998C30.534,7.973,24.027,1.466,16,1.466ZM13.665,25.725L10.129,22.186L16.316,15.998999999999999L10.128999999999998,9.811999999999998L13.664999999999997,6.275999999999998L23.388999999999996,15.998999999999999L13.665,25.725Z"
                          stroke="#ffffff" fill="none" style="stroke-linejoin: round; opacity: 0;"></path>
                    <path fill-opacity="1" opacity="1" transform="matrix(1,0,0,1,4,4)"
                          d="M16,1.466C7.973,1.466,1.466,7.973,1.466,16C1.466,24.027,7.973,30.534,16,30.534C24.027,30.534,30.534,24.027,30.534,15.999999999999998C30.534,7.973,24.027,1.466,16,1.466ZM13.665,25.725L10.129,22.186L16.316,15.998999999999999L10.128999999999998,9.811999999999998L13.664999999999997,6.275999999999998L23.388999999999996,15.998999999999999L13.665,25.725Z"
                          stroke="none" fill="url(#1390-_0050af-_002c62)" style="opacity: 1; fill-opacity: 1;"></path>
                    <rect opacity="0" style="opacity: 0;" stroke="#000" fill="#000000" ry="0" rx="0" r="0" y="0" x="0"></rect>
                </g>
            </svg>
        </a>
    </div>
<?php endif; ?>
<?php if ($props['bar']['push_page'] && $props['bar']['placement'] == 'top'): ?>
    <div class="mf-move-to-parent mf-bar-spacer mf-bar-spacer-<?php echo $props['bar']['size']; ?> mf-bar-spacer-<?php echo $focus['id']; ?>"></div>
<?php endif; ?>
