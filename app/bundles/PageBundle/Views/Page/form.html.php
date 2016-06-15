<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'page');

$variantParent = $activePage->getVariantParent();
$subheader = ($variantParent) ? '<div><span class="small">' . $view['translator']->trans('mautic.page.header.editvariant', array(
    '%name%' => $activePage->getTitle(),
    '%parent%' => $variantParent->getTitle()
)) . '</span></div>' : '';

$header = ($activePage->getId()) ?
    $view['translator']->trans('mautic.page.header.edit',
        array('%name%' => $activePage->getTitle())) :
    $view['translator']->trans('mautic.page.header.new');

$view['slots']->set("headerTitle", $header.$subheader);

$template = $form['template']->vars['data'];
?>

<?php echo $view['form']->start($form); ?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-white height-auto">
        <div class="row">
            <div class="col-xs-12">
                <!-- tabs controls -->
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active"><a href="#theme-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.page.page'); ?></a></li>
                    <li class=""><a href="#source-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.source'); ?></a></li>
                </ul>

                <!--/ tabs controls -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="theme-container">
                        <div class="row">
                            <div class="custom-html-mask">
                                <div class="well text-center" style="top: 110px; width: 50%; margin-left:auto; margin-right:auto;">
                                    <h3 style="padding: 30px;">
                                        <a href="javascript: void(0);" onclick="Mautic.launchBuilder('page');">
                                            <?php echo $view['translator']->trans('mautic.core.builder.launch'); ?> <i class="fa fa-angle-right"></i>
                                        </a>
                                    </h3>
                                </div>
                            </div>

                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php echo $view['form']->row($form['template']); ?>
                            </div>
                        </div>

                        <?php echo $view->render('MauticCoreBundle:Helper:theme_select.html.php', array(
                            'themes' => $themes,
                            'active' => $form['template']->vars['value']
                        )); ?>
                    </div>

                    <div class="tab-pane fade bdr-w-0" id="source-container">
                        <div class="row">
                            <div class="col-md-12" id="customHtmlContainer" style="min-height: 325px;">
                                <?php echo $view['form']->row($form['customHtml']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['title']); ?>
            <?php if (!$isVariant): ?>
            <?php echo $view['form']->row($form['alias']); ?>
            <?php else: ?>
            <?php echo $view['form']->row($form['template']); ?>
            <?php endif; ?>
            <?php
            if ($isVariant):
            echo $view['form']->row($form['variantSettings']);

            else:
            echo $view['form']->row($form['category']);
            echo $view['form']->row($form['language']);
            echo $view['form']->row($form['translationParent']);
            endif;

            echo $view['form']->row($form['isPublished']);
            echo $view['form']->row($form['publishUp']);
            echo $view['form']->row($form['publishDown']);

            if (!$isVariant):
            echo $view['form']->row($form['redirectType']);
            echo $view['form']->row($form['redirectUrl']);
            endif;
            ?>

            <div class="template-fields<?php echo (!$template) ? ' hide"' : ''; ?>">
                <?php echo $view['form']->row($form['metaDescription']); ?>
            </div>

            <div class="hide">
                <?php echo $view['form']->rest($form); ?>
            </div>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>

<!-- <div class="hide builder page-builder">
    <div class="builder-content">
        <input type="hidden" id="builder_url" value="<?php echo $view['router']->path('mautic_page_action', array('objectAction' => 'builder', 'objectId' => $activePage->getSessionId())); ?>" />
    </div>
    <div class="builder-panel">
        <div class="builder-panel-top">
            <p>
                <button type="button" class="btn btn-primary btn-close-builder" onclick="Mautic.closeBuilder('page');"><?php echo $view['translator']->trans('mautic.core.close.builder'); ?></button>
            </p>
            <div class="well well-small mb-10" id="customHtmlDropzone">
            <div class="template-dnd-help<?php echo (!$template) ? ' hide' : ''; ?>"><?php echo $view['translator']->trans('mautic.core.builder.token.help'); ?></div>
                <div class="custom-dnd-help<?php echo (!$template) ? '' : ' hide'; ?>">
                    <div class="custom-drop-message hide text-center"><?php echo $view['translator']->trans('mautic.core.builder.token.drophere'); ?></div>
                    <div class="custom-general-message"><?php echo $view['translator']->trans('mautic.core.builder.token.help_custom'); ?></div>
                </div>
            </div>
        </div>
        <div class="panel-group builder-tokens" id="pageTokensPanel">
            <?php foreach ($tokens as $k => $t): ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?php echo $t['header']; ?></h4>
                    </div>
                    <div class="panel-body">
                        <?php echo $t['content']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div> -->

<div class="hide builder page-builder">
    <script type="text/html" data-builder-assets>
        <?php echo htmlspecialchars($builderAssets); ?>
    </script>
    <div class="builder-content">
        <input type="hidden" id="builder_url" value="<?php echo $view['router']->path('mautic_page_action', array('objectAction' => 'builder', 'objectId' => $activePage->getSessionId())); ?>" />
    </div>
    <div class="builder-panel">
        <div class="builder-panel-top">
            <button type="button" class="btn btn-primary btn-close-builder" onclick="Mautic.closeBuilder('page');">
                <?php echo $view['translator']->trans('mautic.core.close.builder'); ?>
            </button>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">Slot Types</h4>
            </div>
            <div class="panel-body" id="slot-type-container">
                <?php if ($slots): ?>
                    <?php foreach ($slots as $slotKey => $slot): ?>
                        <div class="slot-type-handle btn btn-default btn-lg btn-block" data-slot-type="<?php echo $slotKey; ?>">
                            <i class="fa fa-<?php echo $slot['icon']; ?>" aria-hidden="true"></i>
                            <?php echo $slot['header']; ?>
                            <script type="text/html">
                                <?php echo $view->render($slot['content']); ?>
                            </script>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <p class="text-muted pt-md text-center"><i>Drag the slot type to the desired position.</i></p>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">Customize Slot</h4>
            </div>
            <div class="panel-body" id="customize-form-container">
                <div id="slot-form-container">
                    <p class="text-muted pt-md text-center"><i>Select the slot to customize</i></p>
                </div>
                <?php if ($slots): ?>
                    <?php foreach ($slots as $slotKey => $slot): ?>
                        <script type="text/html" data-slot-type-form="<?php echo $slotKey; ?>">
                            <?php echo $view['form']->start($slot['form']); ?>
                            <?php echo $view['form']->end($slot['form']); ?>
                        </script>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
