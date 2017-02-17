<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$templateBase = 'MauticFocusBundle:Builder\\'.ucfirst($focus['style']).':index.html.php';
if (!isset($preview)) {
    $preview = false;
}

if (!isset($clickUrl)) {
    $clickUrl = '#';
}

$props = $focus['properties'];

?>

<div>
    <style scoped>
        .mautic-focus {
            font-family: <?php echo $props['content']['font']; ?>;
            color: #<?php echo $props['colors']['text']; ?>;
        }

        <?php if (isset($props['colors'])): ?>

        .mf-content a.mf-link, .mf-content .mauticform-button {
            background-color: #<?php echo $props['colors']['button']; ?>;
            color: #<?php echo $props['colors']['button_text']; ?>;
        }

        .mauticform-input:focus, select:focus {
            border: 1px solid #<?php echo $props['colors']['button']; ?>;
        }

        <?php endif; ?>
        <?php
        if (!empty($preview)):
            echo $view->render('MauticFocusBundle:Builder:style.less.php',
                [
                    'preview' => true,
                    'focus' => $focus,
                ]
            );
        endif;
        ?>
    </style>
    <?php echo $view->render(
        $templateBase,
        [
            'focus'    => $focus,
            'form'     => $form,
            'preview'  => $preview,
            'clickUrl' => $clickUrl,
        ]
    );

    // Add view tracking image
    if (!$preview): ?>

        <img src="<?php echo $view['router']->url(
            'mautic_focus_pixel',
            ['id' => $focus['id']],
            true
        ); ?>" alt="Mautic Focus" style="display: none;"/>
    <?php endif; ?>
</div>