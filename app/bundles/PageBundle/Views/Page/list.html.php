<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
$view->extend('MauticPageBundle:Page:index.html.php');
?>

<?php if (count($items)): ?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <?php echo $view['translator']->trans('mautic.page.page.header.index'); ?>
        </h3>
    </div>
    <div class="panel-body">
        <div class="box-layout">
            <div class="col-xs-6 va-m">
                <div class="checkbox-inline custom-primary">
                    <label class="mb-0">
                        <input type="checkbox" id="customcheckbox-one0" value="1">
                        <span></span>
                        <?php echo $view['translator']->trans('mautic.core.table.selectall'); ?>
                    </label>
                </div>
            </div>
            <div class="col-xs-6 va-m text-right">
                <button type="button" class="btn btn-sm btn-warning"><i class="fa fa-files-o"></i></button>
                <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></button>
            </div>
        </div>
    </div>
    <div class="table-responsive scrollable body-white padding-sm page-list">
            <table class="table table-hover table-striped table-bordered pagetable-list">
                <thead>
                <tr>
                    <th class="col-page-actions"></th>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.title',
                        'text'       => 'mautic.page.page.thead.title',
                        'class'      => 'col-page-title',
                        'default'    => true
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'page',
                        'orderBy'    => 'c.title',
                        'text'       => 'mautic.page.page.thead.category',
                        'class'      => 'visible-md visible-lg col-page-category'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.author',
                        'text'       => 'mautic.page.page.thead.author',
                        'class'      => 'visible-md visible-lg col-page-author'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.language',
                        'text'       => 'mautic.page.page.thead.language',
                        'class'      => 'visible-md visible-lg col-page-lang'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.hits',
                        'text'       => 'mautic.page.page.thead.hits',
                        'class'      => 'col-page-hits'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'page',
                        'orderBy'    => 'p.id',
                        'text'       => 'mautic.page.page.thead.id',
                        'class'      => 'col-page-id'
                    ));
                    ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <?php
                    $variantChildren     = $item->getVariantChildren();
                    $translationChildren = $item->getTranslationChildren();
                    ?>
                    <tr>
                        <td>
                            <?php
                            echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                                'item'      => $item,
                                'edit'      => $security->hasEntityAccess(
                                    $permissions['page:pages:editown'],
                                    $permissions['page:pages:editother'],
                                    $item->getCreatedBy()
                                ),
                                'clone'     => $permissions['page:pages:create'],
                                'delete'    => $security->hasEntityAccess(
                                    $permissions['page:pages:deleteown'],
                                    $permissions['page:pages:deleteother'],
                                    $item->getCreatedBy()),
                                'routeBase' => 'page',
                                'menuLink'  => 'mautic_page_index',
                                'langVar'   => 'page.page',
                                'nameGetter' => 'getTitle'
                            ));
                            ?>
                        </td>
                        <td>
                            <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                                'item'       => $item,
                                'model'      => 'page.page'
                            )); ?>
                            <a href="<?php echo $view['router']->generate('mautic_page_action',
                                array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                               data-toggle="ajax">
                                <?php echo $item->getTitle(); ?> (<?php echo $item->getAlias(); ?>)
                            </a>
                            <?php
                            $hasVariants   = count($variantChildren);
                            $hasTranslations = count($translationChildren);
                            if ($hasVariants || $hasTranslations): ?>
                            <span>
                                <?php if ($hasVariants): ?>
                                    <i class="fa fa-fw fa-sitemap"></i>
                                <?php endif; ?>
                                <?php if ($hasTranslations): ?>
                                    <i class="fa fa-fw fa-language"></i>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        </td>
                        <td class="visible-md visible-lg">
                            <?php $catName = ($category = $item->getCategory()) ? $category->getTitle() :
                                $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                            <span><?php echo $catName; ?></span>
                        </td>
                        <td class="visible-md visible-lg"><?php echo $item->getAuthor(); ?></td>
                        <td class="visible-md visible-lg"><?php echo $item->getLanguage(); ?></td>
                        <td class="visible-md visible-lg"><?php echo $item->getHits(); ?></td>
                        <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => count($items),
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_page_index',
            "baseUrl"         => $view['router']->generate('mautic_page_index'),
            'sessionVar'      => 'page'
        )); ?>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="well well-small">
        <h4><?php echo $view['translator']->trans('mautic.core.noresults.header'); ?></h4>
        <p><?php echo $view['translator']->trans('mautic.core.noresults'); ?></p>
    </div>
<?php endif; ?>