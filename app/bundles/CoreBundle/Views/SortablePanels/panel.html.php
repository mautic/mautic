<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$templateVariables = [
    'panelId' => $panelId,
    'panel'   => $panel,
];

if (isset($panel['templateVariables'])) {
    $templateVariables = array_merge($templateVariables, $panel['templateVariables']);
}

$actionsTemplate = empty($panel['actionsTemplate']) ?
    'MauticCoreBundle:SortablePanels:actions.html.php' : $panel['actionsTemplate'];
?>

<div class="panel<?php if (isset($panel['class'])) {
    echo ' '.$panel['class'];
} ?>" data-sortable-id="panel_<?php echo $panelId; ?>">
    <div class="sortable-panel-wrapper">
        <?php echo $view->render($actionsTemplate, $templateVariables); ?>
        <div class="row ml-0 mr-0 sortable-panel-content">
            <?php
            if (isset($panel['template'])):
            echo $view->render($panel['template'], $templateVariables);
            else:
            echo '<span class="sortable-panel-label">'.(isset($panel['label']) ? $panel['label'] : '').'</span>';
            endif;
            ?>
        </div>
        <?php if (!empty($panel['footer']) || !empty($panel['footerTemplate'])): ?>
        <div class="panel-footer sortable-panel-footer">
            <?php
            if (!empty($panel['footer'])):
            echo $panel['footer'];
            endif;

            if (!empty($panel['footerTemplate'])):
                echo $view->render($panel['footerTemplate'], $templateVariables);
            endif;
            ?>
        </div>
        <?php endif; ?>
    </div>

</div>
