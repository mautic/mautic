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
        <div class="pa-md">
            <div class="row">
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['title']); ?>
                </div>
                <div class="col-md-6">
                    <?php if (!$isVariant): ?>
                    <?php echo $view['form']->row($form['alias']); ?>
                    <?php else: ?>
                    <?php echo $view['form']->row($form['template']); ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$isVariant): ?>
            <div class="row">
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['template']); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12" id="customHtmlContainer" style="min-height: 325px;">
                    <div class="custom-html-mask<?php if (!$template) echo ' hide'; ?>">
                        <div class="well text-center" style="position: absolute; top: 110px; width: 50%; left:0; right:0; margin-left:auto; margin-right:auto; z-index: 1010;">
                            <h3 style="padding: 30px;">
                                <a href="javascript: void(0);" onclick="Mautic.launchBuilder('page');">
                                    <?php echo $view['translator']->trans('mautic.core.builder.launch'); ?> <i class="fa fa-angle-right"></i>
                                </a>
                            </h3>
                        </div>
                        <div style="height: 100%; width: 100%; position: absolute; top: 0; left: 0; bottom: 0; right: 0; opacity: 0.8; background-color: #fff; z-index: 1000"></div>
                    </div>
                    <?php echo $view['form']->row($form['customHtml']); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
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
        <input type="hidden" id="builder_url" value="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'builder', 'objectId' => $activePage->getSessionId())); ?>" />
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

            <?php echo $view->render('MauticCoreBundle:Helper:buildermodal_feedback.html.php'); ?>
            <?php echo $view->render('MauticCoreBundle:Helper:buildermodal_link.html.php'); ?>
        </div>
    </div>
</div>
