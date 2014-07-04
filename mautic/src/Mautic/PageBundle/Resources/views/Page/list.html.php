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
    $activeClass = ($tmpl == 'index' && !empty($activePage) && $item->getId() === $activePage->getId()) ? " active" : "";
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
        <a href="<?php echo $view['router']->generate('mautic_page_action',
            array(
                'objectAction' => 'view',
                'objectId' => $item->getId(),
                'tmpl' => 'page'
            )); ?>"
           onclick="Mautic.activateListItem('page', <?php echo $item->getId(); ?>);"
           data-toggle="ajax"
           data-menu-link="mautic_page_index">
            <span class="list-item-primary">
                <?php echo $item->getTitle(); ?>
            </span>
            <span class="list-item-secondary list-item-indent">
                <?php
                $createdBy = $item->getCreatedBy();
                $author = (empty($createdBy)) ? $item->getAuthor() : $createdBy->getName();
                echo $author;
                ?>
            </span>
        </a>
        <div class="badge-count padding-sm">
            <span class="badge" data-toggle="tooltip"
                  title="<?php echo $view['translator']->trans('mautic.page.page.numhits'); ?>">
                <?php echo $item->getHits(); ?>
            </span>
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
    "menuLinkId"      => 'mautic_page_index',
    "baseUrl"         => $view['router']->generate('mautic_page_index'),
    "queryString"     => 'tmpl=list',
    "paginationClass" => "sm",
    'sessionVar'      => 'page',
    'tmpl'            => 'list'
)); ?>
<?php else: ?>
<h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
<?php endif; ?>

<div class="page-footer"></div>