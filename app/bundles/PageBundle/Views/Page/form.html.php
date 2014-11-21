<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'page');

$variantParent = $activePage->getVariantParent();
$subheader = ($variantParent) ? '<span class="small"> - ' . $view['translator']->trans('mautic.page.page.header.editvariant', array(
    '%name%' => $activePage->getTitle(),
    '%parent%' => $variantParent->getTitle()
)) . '</span>' : '';

$header = ($activePage->getId()) ?
    $view['translator']->trans('mautic.page.page.header.edit',
        array('%name%' => $activePage->getTitle())) :
    $view['translator']->trans('mautic.page.page.header.new');

$view['slots']->set("headerTitle", $header.$subheader);
?>

<?php echo $view['form']->start($form); ?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto">
        <div class="pa-md">
            <div class="row">
                <div class="col-sm-12">
                    <?php echo $view['form']->row($form['title']); ?>
                    <?php echo $view['form']->row($form['alias']); ?>
                    <?php echo $view['form']->row($form['template']); ?>
                    <?php echo $view['form']->row($form['metaDescription']); ?>
                    <?php if (isset($form['variantSettings'])): ?>
                    <?php echo $view['form']->row($form['variantSettings']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php
                echo $view['form']->row($form['category']);
                echo $view['form']->row($form['language']);
                echo $view['form']->row($form['translationParent_lookup']);
                echo $view['form']->row($form['translationParent']);
                echo $view['form']->row($form['isPublished']);
                echo $view['form']->row($form['publishUp']);
                echo $view['form']->row($form['publishDown']);
                echo $view['form']->rest($form);
            ?>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>

<div class="hide builder page-builder">
    <div class="builder-content">
        <input type="hidden" id="pageBuilderUrl" value="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'builder', 'objectId' => $activePage->getSessionId())); ?>" />
    </div>
    <div class="builder-panel">
        <p>
            <button type="button" class="btn btn-primary btn-close-builder" onclick="Mautic.closePageEditor();"><?php echo $view['translator']->trans('mautic.page.page.builder.close'); ?></button>
        </p>
        <div class="well well-small"><?php echo $view['translator']->trans('mautic.page.page.token.help'); ?></div>
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
