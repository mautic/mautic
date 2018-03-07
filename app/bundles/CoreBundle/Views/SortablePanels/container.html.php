<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!isset($containerId)) {
    $containerId = 'panel-container';
}

if (!isset($panelTemplate)) {
    $panelTemplate = 'MauticCoreBundle:SortablePanels:panel.html.php';
}

if (!isset($prototypePanelTemplates)) {
    $prototypePanelTemplates = [];
}

if (!isset($deletedPanels)) {
    $deletedPanels = [];
}

if (!isset($colSize)) {
    $colSize = 6;
}
?>
<div class="row">
    <div id="<?php echo $containerId; ?>" class="col-sm-12 sortable-panels" data-index="<?php echo count($panels); ?>">
        <?php if (!empty($availablePanels)): ?>
        <div class="available-panels mb-md">
            <div class="row">
                <div class="col-md-<?php echo $colSize; ?>">
                    <?php if (!empty($availablePanelsLabel)): ?>
                    <label class="control-label" for=<?php $containerId; ?>AvailablePanels">
                        <?php echo $view['translator']->trans($availablePanelsLabel); ?>
                        <?php if (!empty($availablePanelsLabelTooltip)): ?>
                        <i class="fa fa-question-circle" data-toggle="tooltip" title="<?php echo $view['translator']->trans($availablePanelsLabelTooltip); ?>"></i>
                        <?php endif; ?>
                    </label>
                    <?php endif; ?>
                    <select id="<?php $containerId; ?>AvailablePanels"
                            class="available-panel-selector"
                            data-prototype-prefix="<?php echo $prototypeContainerPrefix; ?>"
                            data-prototype-id-prefix="<?php echo $prototypeIdPrefix; ?>"
                            data-prototype-name-prefix="<?php echo $prototypeNamePrefix; ?>"
                            <?php if (isset($appendPanelCallback)): echo ' data-append-sortable-callback="'.$appendPanelCallback.'"'; endif; ?>
                            <?php if (!empty($appendPanelMessage)): echo ' data-chosen-placeholder="'.$view['translator']->trans($appendPanelMessage).'"'; endif; ?>>
                        <option value=""></option>
                        <?php
                        foreach ($availablePanels as $groupId => $groupPanels):
                            if (!is_array($groupPanels) || isset($groupPanels['label'])):
                                $optionGroup = false;
                                $groupPanels = [
                                    $groupId => (!is_array($groupPanels)) ? ['value' => $groupId, 'label' => $groupPanels] : $groupPanels,
                                ];
                            else:
                                $optionGroup = $groupId; // optgroup
                            endif;
                        ?>
                        <?php if ($optionGroup): ?><optgroup label="<?php echo $view['translator']->trans($groupId); ?>"><?php endif; ?>
                        <?php foreach ($groupPanels as $panelId => $panel): ?>
                        <option id="<?php echo $panelId; ?>" value="<?php echo $view->escape($panel['value']); ?>"
                                data-default-label="<?php echo $view->escape($panel['label']); ?>"
                            <?php if (isset($panel['attr'])): echo $panel['attr']; endif ?>
                            <?php if (!empty($panel['prototypeTemplatePlaceholders'])):
                                echo ' data-placeholders="'.$view->escape(json_encode($panel['prototypeTemplatePlaceholders'])).'"';
                            endif; ?>
                        >
                            <?php echo $panel['label']; ?>
                        </option>
                        <?php if ($optionGroup): ?></optgroup><?php endif; ?>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="drop-here">

        <?php
        foreach ($panels as $panelId => $panel):
        if (!in_array($panelId, $deletedPanels)):
            echo $view->render($panelTemplate, ['panelId' => $panelId, 'panel' => $panel]);
        endif;
        endforeach;
        ?>

        <?php if (!count($panels)): ?>
            <div class="alert alert-info col-md-<?php echo $colSize; ?> sortable-panel-placeholder">
                <p>
                    <?php echo $view['translator']->trans((isset($noPanelMessage) ? $noPanelMessage : 'mautic.channel.form.additem')); ?>
                </p>
            </div>
        <?php endif; ?>
        </div>

        <div class="panel-prototype hide">
            <?php echo $view->render($panelTemplate, ['panelId' => 'prototype', 'panel' => $prototypePanelTemplates]); ?>
        </div>
    </div>
</div>
