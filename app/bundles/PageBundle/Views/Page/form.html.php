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
    <div class="col-md-9 bg-auto height-auto">
        <div class="row">
            <div class="col-xs-12">
                <!-- tabs controls -->
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active"><a href="#theme-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.theme'); ?></a></li>
                    <li class=""><a href="#source-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.source'); ?></a></li>
                </ul>
            </div>
        </div>

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

                    <?php echo $view['form']->row($form['template']); ?>
                </div>

                <?php if ($themes) : ?>
                    <div class="row">
                        <?php foreach ($themes as $themeKey => $themeInfo) : ?>
                            <?php $thumbnailUrl = $view['assets']->getUrl('themes/'.$themeKey.'/thumbnail.png'); ?>
                            <?php $hasThumbnail = file_exists($themeInfo['dir'].'/thumbnail.png'); ?>
                            <?php $isSelected = ($form['template']->vars['value'] === $themeKey); ?>
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

<div class="hide builder page-builder">
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
</div>
