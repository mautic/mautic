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
<div class="hide builder <?php echo $type; ?>-builder <?php echo $isCodeMode ? 'code-mode' : ''; ?>">
    <script type="text/html" data-builder-assets>
        <?php echo htmlspecialchars($builderAssets); ?>
    </script>
    <div class="builder-content">
        <input type="hidden" id="builder_url" value="<?php echo $view['router']->path('mautic_'.$type.'_action', ['objectAction' => 'builder', 'objectId' => $objectId]); ?>" />
    </div>
    <div class="builder-panel">
        <div class="builder-panel-top">
            <?php echo $view->render('MauticCoreBundle:Helper:builder_buttons.html.php', [
                'onclick'       => "Mautic.closeBuilder('$type');",
            ]); ?>

            <div class="code-mode-toolbar <?php echo $isCodeMode ? '' : 'hide'; ?>">
                <button class="btn btn-default btn-nospin" onclick="Mautic.openMediaManager()" data-toggle="tooltip" data-placement="bottom" title="<?php echo $view['translator']->trans('mautic.core.media.manager.desc'); ?>">
                    <i class="fa fa-photo"></i>
                    <?php echo $view['translator']->trans('mautic.core.media.manager'); ?>
                </button>
                <button class="btn btn-default btn-nospin" onclick="Mautic.formatCode()" data-toggle="tooltip" data-placement="bottom" title="<?php echo $view['translator']->trans('mautic.core.format.code.desc'); ?>">
                    <i class="fa fa-indent"></i>
                    <?php echo $view['translator']->trans('mautic.core.format.code'); ?>
                </button>
            </div>
        </div>
        <div class="code-editor <?php echo $isCodeMode ? '' : 'hide'; ?>">
            <div id="customHtmlContainer"></div>
            <i class="text-muted"><?php echo $view['translator']->trans('mautic.core.code.mode.token.dropdown.hint'); ?></i>
        </div>
        <div class="builder-toolbar <?php echo $isCodeMode ? 'hide' : ''; ?>">
            <div class="panel panel-default" id="preview">
                <div class="panel-heading">
                    <h4 class="panel-title"><?php echo $view['translator']->trans('mautic.email.urlvariant'); ?></h4>
                </div>
                <div class="panel-body">
                    <div id="public-preview-container" class="col-md-12">
                        <div class="input-group">
                            <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control"
                                   readonly
                                   value="<?php echo $previewUrl; ?>"/>
                            <span class="input-group-btn">
                                <button class="btn btn-default btn-nospin" onclick="window.open('<?php echo $previewUrl; ?>', '_blank');">
                                    <i class="fa fa-external-link"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><?php echo $view['translator']->trans('mautic.core.slot.types'); ?></h4>
                </div>
                <div class="panel-body">
                    <?php if ($slots): ?>
                    <div id="slot-type-container" class="col-md-12">
                        <?php foreach ($slots as $slotKey => $slot): ?>
                            <div class="slot-type-handle btn btn-default btn-lg btn-nospin" data-slot-type="<?php echo $slotKey; ?>">
                                <i class="fa fa-<?php echo $slot['icon']; ?>" aria-hidden="true"></i>
                                <br>
                                <span class="slot-caption"><?php echo $slot['header']; ?></span>
                                <script type="text/html">
                                    <?php echo $view->render($slot['content'], isset($slot['params']) ? $slot['params'] : []); ?>
                                </script>
                            </div>
                        <?php endforeach; ?>
                        <div class="clearfix"></div>
                    </div>
                    <?php endif; ?>
                    <p class="text-muted pt-md text-center"><i><?php echo $view['translator']->trans('mautic.core.drag.info'); ?></i></p>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><?php echo $view['translator']->trans('mautic.core.section.types'); ?></h4>
                </div>
                <div class="panel-body">
                    <?php if ($sections): ?>
                    <div id="section-type-container" class="col-md-12">
                        <?php foreach ($sections as $sectionKey => $section): ?>
                            <div class="section-type-handle btn btn-default btn-lg btn-nospin" data-section-type="<?php echo $sectionKey; ?>">
                                <i class="fa fa-<?php echo $section['icon']; ?>" aria-hidden="true"></i>
                                <br>
                                <span class="slot-caption"><?php echo $section['header']; ?></span>
                                <script type="text/html">
                                    <?php echo $view->render($section['content']); ?>
                                </script>
                            </div>
                        <?php endforeach; ?>
                        <div class="clearfix"></div>
                    </div>
                    <?php endif; ?>
                    <p class="text-muted pt-md text-center"><i><?php echo $view['translator']->trans('mautic.core.drag.info'); ?></i></p>
                </div>
            </div>

            <div class="panel panel-default" id="customize-slot-panel">
                <div class="panel-heading">
                    <h4 class="panel-title"><?php echo $view['translator']->trans('mautic.core.customize.slot'); ?></h4>
                </div>
                <div class="panel-body" id="customize-form-container">
                    <div id="slot-form-container" class="col-md-12">
                        <p class="text-muted pt-md text-center">
                            <i><?php echo $view['translator']->trans('mautic.core.slot.customize.info'); ?></i>
                        </p>
                    </div>
                    <?php if ($slots): ?>
                        <?php foreach ($slots as $slotKey => $slot): ?>
                            <script type="text/html" data-slot-type-form="<?php echo $slotKey; ?>">
                                <?php echo $view['form']->form($slot['form']); ?>
                            </script>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel panel-default" id="section">
                <div class="panel-heading">
                    <h4 class="panel-title"><?php echo $view['translator']->trans('mautic.core.customize.section'); ?></h4>
                </div>
                <div class="panel-body" id="customize-form-container">
                    <div id="section-form-container" class="col-md-12">
                        <p class="text-muted pt-md text-center">
                            <i><?php echo $view['translator']->trans('mautic.core.section.customize.info'); ?></i>
                        </p>
                    </div>
                    <script type="text/html" data-section-form>
                        <?php echo $view['form']->form($sectionForm); ?>
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
