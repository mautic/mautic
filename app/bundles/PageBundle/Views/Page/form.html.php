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

<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-r">
        <div class="pa-md">
            <div class="row">
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['title']); ?>
                </div>
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['alias']); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['category_lookup']); ?>
                    <?php echo $view['form']->row($form['category']); ?>
                </div>
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['template']); ?>
                </div>
            </div>
        </div>

        <div class="hide page-builder">
            <div class="page-builder-content">
                <input type="hidden" id="pageBuilderUrl" value="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'builder', 'objectId' => $activePage->getSessionId())); ?>" />
            </div>
            <div class="page-builder-panel">
                <p>
                    <button class="btn btn-danger btn-close-builder" onclick="Mautic.closePageEditor();"><?php echo $view['translator']->trans('mautic.page.page.builder.close'); ?></button>
                </p>
                <div><em><?php echo $view['translator']->trans('mautic.page.page.token.help'); ?></em></div>
                <div class="panel-group margin-sm-top" id="page_tokens">
                    <?php foreach ($tokens as $k => $t): ?>
                    <?php $id = \Mautic\CoreBundle\Helper\InputHelper::alphanum($k); ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a style="display: block;" data-toggle="collapse" data-parent="#page_tokens" href="#<?php echo $id; ?>">
                                    <span class="pull-left">
                                        <?php echo $t['header']; ?>
                                    </span>
                                    <span class="pull-right">
                                        <i class="fa fa-lg fa-fw fa-angle-down"></i>
                                    </span>
                                    <div class="clearfix"></div>
                                </a>
                            </h4>
                        </div>
                        <div id="<?php echo $id; ?>" class="panel-collapse collapse">
                            <div class="panel-body">
                                <?php echo $t['content']; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php
                echo $view['form']->row($form['category_lookup']);
                echo $view['form']->row($form['category']);
                echo $view['form']->row($form['language']);
                echo $view['form']->row($form['template']);
                echo $view['form']->row($form['translationParent_lookup']);
                echo $view['form']->row($form['translationParent']);
                echo $view['form']->row($form['isPublished']);
                echo $view['form']->row($form['publishUp']);
                echo $view['form']->row($form['publishDown']);
                echo $view['form']->end($form);
            ?>
        </div>
    </div>
</div>