<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php
if (count($items)):
foreach ($items as $key => $item):
    $activeClass = ($tmpl == 'index' && !empty($activeCategory) && $item->getId() === $activeCategory->getId()) ? " active" : "";
    ?>
<div class="bundle-list-item<?php echo $activeClass; ?>" id="page-<?php echo $item->getId(); ?>">
    <div class="padding-sm">
        <span class="list-item-publish-status">
            <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                'item'       => $item,
                'dateFormat' => (!empty($dateFormat)) ? $dateFormat : 'F j, Y g:i a',
                'model'      => 'page.page'
            )); ?>
        </span>
        <a href="<?php echo $view['router']->generate('mautic_pagecategory_action',
            array(
                'objectAction' => 'view',
                'objectId' => $item->getId(),
                'tmpl' => 'page'
            )); ?>"
           onclick="Mautic.activateListItem('page', <?php echo $item->getId(); ?>);"
           data-toggle="ajax"
           data-menu-link="mautic_pagecategory_index">
            <span class="list-item-primary">
                <?php echo $item->getTitle(); ?>
            </span>
            <span class="list-item-secondary list-item-indent">
                <?php echo $item->getDescription(); ?>
            </span>
        </a>
        <div class="badge-count padding-sm">
            <a href="<?php echo $view['router']->generate('mautic_page_index', array(
                'search' => $view['translator']->trans('mautic.core.searchcommand.category') . ":" . $item->getAlias())
            ); ?>"
               data-toggle="ajax"
               data-menu-link="mautic_page_index"
               class="has-click-event"
                >
                <span class="badge" data-toggle="tooltip"
                      title="<?php echo $view['translator']->trans('mautic.page.category.numpages'); ?>">
                    <?php echo count($item->getPages()); ?>
                </span>
            </a>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
<?php endforeach; ?>
<?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
    "items"           => $items,
    "page"            => $page,
    "limit"           => $limit,
    "totalItems"      => $totalCount,
    "menuLinkId"      => 'mautic_pagecategory_index',
    "baseUrl"         => $view['router']->generate('mautic_pagecategory_index'),
    "queryString"     => 'tmpl=list',
    "paginationClass" => "sm",
    'sessionVar'      => 'page',
    'tmpl'            => 'list'
)); ?>
<?php else: ?>
<h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
<?php endif; ?>

<div class="footer-margin"></div>