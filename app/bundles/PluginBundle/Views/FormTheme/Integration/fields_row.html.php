<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var int $numberOfFields */
$rowCount   = 0;
$indexCount = 1;

$baseUrl = $view['router']->path(
    'mautic_plugin_config',
    [
        'name' => $integration,
    ]
);

?>

<div class="row fields-container" id="<?php echo $containerId; ?>">
    <?php if (!empty($specialInstructions)): ?>
        <div class="alert alert-<?php echo $alertType; ?>">
            <?php echo $view['translator']->trans($specialInstructions); ?>
        </div>
    <?php endif; ?>
    <div class="<?php echo $object; ?>-field form-group col-xs-12">
        <div class="row">
            <div class="mb-xs ml-lg pl-xs pr-xs col-sm-4 text-center"><h4><?php echo $view['translator']->trans('mautic.plugins.integration.fields'); ?></h4></div>
            <?php if ($numberOfFields == 5): ?>
                <div class="pl-xs pr-xs col-sm-2"></div>
            <?php endif; ?>
            <div class="pl-xs pr-xs col-sm-4 text-center"><h4><?php echo $view['translator']->trans('mautic.plugins.mautic.fields'); ?></h4></div>
            <div class="pl-xs pr-xs col-sm-1 text-center"></div>
        </div>
        <?php echo $view['form']->errors($form); ?>
        <?php foreach ($form->children as $child):
            $selected = false;
            ?>
            <?php if (isset($child->vars['attr']['data-required'])) : $isRequired = !empty($child->vars['attr']['data-required']); endif; ?>
            <?php if ($rowCount % $numberOfFields == 0):  ?>
            <div id="<?php echo $object; ?>-<?php echo $rowCount; ?>" class="field-container row">
            <?php endif; ?>
            <?php ++$rowCount; ?>
            <?php
            if ('hidden' === $child->vars['block_prefixes'][1]):
                echo $view['form']->row($child);
            else:
                $class = '';
                switch (true):
                    case $rowCount % $numberOfFields == 1:
                        $class = (5 === $numberOfFields) ? 'ml-lg col-sm-4' : 'ml-lg col-sm-5';
                        break;
                    case $rowCount % $numberOfFields == 2:
                        $class = (5 === $numberOfFields) ? 'ml-xs col-sm-2' : 'col-sm-5';
                        break;
                    case $rowCount % $numberOfFields == 3:
                        $class = 'col-sm-4';
                        break;
                endswitch;
            endif;
            if ($child->vars['name'] == 'label_'.$indexCount):
                if ($isRequired):
                    $name = $child->vars['full_name'];
                    echo '<input type="hidden" value="'.$child->vars['value'].'" name="'.$name.'" />';
                endif;
                ?>
                <div class="pl-xs pr-xs <?php echo $class; ?>">
                    <div class="placeholder" data-placeholder="<?php echo $child->vars['attr']['placeholder']; ?>">
                        <?php echo $view['form']->row($child); ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (strstr($child->vars['name'], 'update_mautic')): ?>
            <div class="pl-xs pr-xs <?php echo $class; ?>" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.plugin.direction.data.update'); ?>">
                <div class="row">
                    <div class="form-group col-xs-12 ">
                        <div class="choice-wrapper">
                            <div class="btn-group btn-block" data-toggle="buttons">
                                <?php $checked = $child->vars['value'] === '0'; ?>
                                <label class="btn btn-default<?php if ($checked): echo ' active'; endif; ?>">
                                    <input type="radio"
                                           id="<?php echo $child->vars['id']; ?>_0"
                                           name="<?php echo $child->vars['full_name']; ?>"
                                           data-toggle="tooltip"
                                           title=""
                                           autocomplete="false"
                                           value="0"
                                           onchange="Mautic.matchedFields(<?php echo $indexCount; ?>, '<?php if ($object == 'contact'): echo 'lead'; else:  echo 'company'; endif; ?>', '<?php echo $integration; ?>')"
                                           <?php if ($checked): ?>checked="checked"<?php endif; ?>>
                                    <btn class="btn-nospin fa fa-arrow-circle-left"></btn>
                                </label>
                                <?php $checked = $child->vars['value'] === '1'; ?>
                                <label class="btn btn-default<?php if ($checked): echo ' active'; endif; ?>">
                                    <input type="radio" id="<?php echo $child->vars['id']; ?>_1"
                                           name="<?php echo $child->vars['full_name']; ?>"
                                           data-toggle="tooltip"
                                           title=""
                                           autocomplete="false"
                                           value="1"
                                           onchange="Mautic.matchedFields(<?php echo $indexCount; ?>, '<?php if ($object == 'contact'): echo 'lead'; else:  echo 'company'; endif; ?>', '<?php echo $integration; ?>')"
                                           <?php if ($child->vars['value'] === '1'): ?>checked="checked"<?php endif; ?>>
                                    <btn class="btn-nospin fa fa-arrow-circle-right"></btn>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php
            if ($child->vars['name'] == 'm_'.$indexCount):
                ?>
            <div class="pl-xs pr-xs <?php echo $class; ?>">
                <select id="<?php echo $child->vars['id']; ?>"
                        name="<?php echo $child->vars['full_name']; ?>"
                        class="<?php echo $child->vars['attr']['class']?>"
                        data-placeholder=" "
                        autocomplete="false" onchange="Mautic.matchedFields(<?php echo $indexCount; ?>, '<?php if ($object == 'contact'): echo 'lead'; else:  echo 'company'; endif; ?>', '<?php echo $integration; ?>')">
                    <?php
                    $mauticChoices = $child->vars['attr']['data-choices'];
                    foreach ($mauticChoices as $keyLabel => $options): ?>
                    <?php if (is_array($options)) : ?>
                    <optgroup label="<?php echo $keyLabel; ?>">
                        <?php foreach ($options as $keyValue => $o): ?>
                        <option value="<?php echo $keyValue; ?>" <?php if ($keyValue === $child->vars['data']): echo 'selected'; $selected = true; elseif (empty($selected) && $keyValue == '-1'): echo 'selected'; endif; ?>>
                            <?php echo $o; ?>
                        </option>
                        <?php endforeach; ?>

                    </optgroup>
                    <?php else : ?>
                    <option value="<?php echo $keyLabel; ?>" <?php if ($keyLabel === $child->vars['data']): echo 'selected'; $selected = true; elseif (empty($selected) && $keyLabel == '-1'): echo 'selected'; endif; ?>>
                        <?php echo $options; ?>
                    </option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
                <div class="pl-xs pr-xs ml-xs col-sm-1">
                    <?php if ($object == 'contact'): $use = 'lead'; else : $use = 'company'; endif; ?>
                <div id="matched-<?php echo $indexCount.'-'.$use; ?>"><?php if ($selected) : ?><i class="fa fa-check-circle text-success"></i><?php endif; ?></div>
                </div>
                    <?php endif; ?>

            <?php if ($rowCount % $numberOfFields == 0): ?>
                </div>
                <?php
                ++$indexCount;
            endif;
            ?>
        <?php endforeach; ?>
    </div>
    <div class="panel-footer">

        <?php echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            [
                'page'        => $page,
                'fixedPages'  => $fixedPageNum,
                'fixedLimit'  => true,
                'baseUrl'     => $baseUrl,
                'target'      => '.modal-body-content',
                'totalItems'  => $totalFields,
                'queryString' => '&activeTab='.$containerId,
            ]
        ); ?>
    </div>
</div>