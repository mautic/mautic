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

$contentMode = $form['contentMode']->vars['data'];
?>

<?php echo $view['form']->start($form); ?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto">
        <div class="pa-md">
            <div class="row">
                <div class="col-sm-12">
                    <div class="row">
                        <div class="col-md-6">
                            <?php echo $view['form']->row($form['title']); ?>
                        </div>
                        <div class="col-md-6">
                            <?php
                            if (isset($form['variantSettings'])):
                                echo $view['form']->row($form['contentMode']);
                            else:
                                echo $view['form']->row($form['alias']);
                            endif;
                            ?>
                        </div>
                    </div>

                    <?php if (!isset($form['variantSettings'])): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <?php echo $view['form']->row($form['contentMode']); ?>
                        </div>
                        <div class="col-md-6"></div>
                    </div>
                    <?php endif; ?>

                    <div id="builderHtmlContainer" class="row <?php echo ($contentMode == 'custom') ? 'hide"' : ''; ?>">
                        <div class="col-md-6">
                            <?php echo $view['form']->row($form['template']); ?>
                            <button type="button" class="btn btn-primary" onclick="Mautic.launchBuilder('page');"><i class="fa fa-cube text-mautic "></i> <?php echo $view['translator']->trans('mautic.page.launch.builder'); ?></button>
                        </div>
                        <div class="col-md-6"></div>
                    </div>

                    <div id="customHtmlContainer"<?php echo ($contentMode == 'builder') ? ' class="hide"' : ''; ?>>
                        <?php echo $view['form']->row($form['customHtml']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php
            if (isset($form['variantSettings'])):
            echo $view['form']->row($form['variantSettings']);

            else:
            echo $view['form']->row($form['category']);
            echo $view['form']->row($form['language']);
            echo $view['form']->row($form['translationParent']);
            endif;

            echo $view['form']->row($form['isPublished']);
            echo $view['form']->row($form['publishUp']);
            echo $view['form']->row($form['publishDown']);
            ?>
            <div id="metaDescriptionContainer"<?php echo ($contentMode == 'custom') ? ' class="hide"' : ''; ?>>
                <?php echo $view['form']->row($form['metaDescription']); ?>
            </div>
            <?php echo $view['form']->rest($form); ?>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>

<div class="hide builder page-builder">
    <div class="builder-content">
        <input type="hidden" id="builder_url" value="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'builder', 'objectId' => $activePage->getSessionId())); ?>" />
    </div>
    <div class="builder-panel">
        <p>
            <button type="button" class="btn btn-primary btn-close-builder" onclick="Mautic.closeBuilder('page');"><?php echo $view['translator']->trans('mautic.page.builder.close'); ?></button>
        </p>
        <div class="well well-small"><?php echo $view['translator']->trans('mautic.page.token.help'); ?></div>
        <div class="panel-group margin-sm-top" id="pageTokensPanel">
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
