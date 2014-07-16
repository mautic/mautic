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
$view["slots"]->set("headerTitle", $view['translator']->trans('mautic.page.page.header.index'));
$searchBtnClass  = (!empty($searchValue)) ? "fa-eraser" : "fa-search";
$searchBtnAction = (!empty($searchValue)) ? 1 : 0; //clear or populate
$activeClass     = "";
?>
<?php $view["slots"]->start("actions"); ?>
<?php if ($permissions['page:pages:create']): ?>
    <li>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_page_action', array("objectAction" => "new")); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_page_index">
            <?php echo $view["translator"]->trans("mautic.page.page.menu.new"); ?>
        </a>
    </li>
<?php endif; ?>

<?php $view["slots"]->stop(); ?>

<div class="row bundle-content-wrapper">
    <div class="col-xs-12 col-sm-4 bundle-side auto-height">
        <div class="rounded-corners body-white bundle-side-inner-wrapper padding-sm">
            <div class="bundle-side-filter-container">
                <div class="input-group">
                    <div class="input-group-btn">
                        <button class="btn btn-default" data-toggle="modal" data-target="#search-help">
                            <i class="fa fa-question-circle"></i>
                        </button>
                    </div>
                    <input type="search"
                           class="form-control"
                           id="list-search" name="search"
                           placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>"
                           value="<?php echo $searchValue; ?>"
                           autocomplete="off"
                           data-toggle="livesearch"
                           data-target=".bundle-list"
                           data-action="<?php echo $view['router']->generate('mautic_page_index', array('page' => $page)); ?>"
                           data-overlay-text="<?php echo $view['translator']->trans('mautic.core.search.livesearch'); ?>"
                           data-overlay-background="#ffffff"
                        />
                    <div class="input-group-btn">
                        <button class="btn btn-default btn-search btn-filter"
                                data-livesearch-parent="list-search">
                            <i class="fa <?php echo $searchBtnClass; ?> fa-fw"></i>
                        </button>
                    </div>
                </div>
                <div class="hidden-shelf">
                    <div class="shelf-contents collapse">
                        <?php $catCommand = $view['translator']->trans('mautic.core.searchcommand.category') . ':'; ?>
                        <select class="form-control" autocomplete="off"
                                onchange="Mautic.setSearchFilter(this);">
                            <option value=""><?php echo $view['translator']->trans('mautic.core.form.categories'); ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $catCommand . $cat['alias']; ?>">
                                    <?php echo $cat['title'] . " ({$cat['alias']})"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="shelf-handle">
                        <i class="fa fa-chevron-circle-down"></i>
                    </div>
                </div>
            </div>
            <div class="bundle-list scrollable">
                <?php echo $view->render('MauticPageBundle:Page:list.html.php', array(
                    'items'       => $items,
                    'page'        => $page,
                    'activePage'  => $activePage,
                    'limit'       => $limit,
                    'totalCount'  => $totalCount,
                    'tmpl'        => $tmpl
                )); ?>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-8 bundle-main auto-height">
        <i class="fa fa-arrows-alt expand-panel" onclick="Mautic.expandPanel('.bundle-main');"></i>
        <div class="rounded-corners body-white bundle-main-inner-wrapper scrollable padding-lg-sides">
            <?php $view['slots']->output('_content'); ?>
        </div>
    </div>
</div>
<?php
echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'search-help',
    'header' => $view['translator']->trans('mautic.core.search.header'),
    'body'   => $view['translator']->trans('mautic.core.search.help') .
        $view['translator']->trans('mautic.page.page.help.searchcommands')
));
?>