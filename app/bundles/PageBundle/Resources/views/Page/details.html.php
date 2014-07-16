<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo - add landing page stats/analytics

if ($tmpl == 'index') {
    $view->extend('MauticPageBundle:Page:index.html.php');
}
?>

<?php
if (!empty($activePage)):
$variantParent = $activePage->getVariantParent();

if (empty($variantParent) && $permissions['page:pages:create']):
$custom = <<<CUSTOM
<li>
    <a href="{$view['router']->generate('mautic_page_action',
           array("objectAction" => "abtest", "objectId" => $activePage->getId()))}"
        data-toggle="ajax"
        data-menu-link="mautic_page_index">
    <span><i class="fa fa-sitemap"></i>{$view['translator']->trans('mautic.page.page.form.abtest')}</span>
    </a>
</li>
CUSTOM;
endif;
?>

<div class="bundle-main-header">
    <span class="bundle-main-item-primary">
        <span class="bundle-main-actions">
            <?php
            echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                'item'       => $activePage,
                'edit'       => $security->hasEntityAccess(
                    $permissions['page:pages:editown'],
                    $permissions['page:pages:editother'],
                    $activePage->getCreatedBy()
                ),
                'clone'      => $permissions['page:pages:create'],
                'delete'     => $security->hasEntityAccess(
                    $permissions['page:pages:deleteown'],
                    $permissions['page:pages:deleteother'],
                    $activePage->getCreatedBy()),
                'routeBase'  => 'page',
                'menuLink'   => 'mautic_page_index',
                'langVar'    => 'page.page',
                'nameGetter' => 'getTitle',
                'custom'     => !empty($custom) ? $custom : ''
            ));
            ?>
        </span>
        <?php echo $view['translator']->trans($activePage->getTitle()); ?> <span class="small">(<?php echo $activePage->getAlias(); ?>)</span>
    </span>
    <?php
    if ($category = $activePage->getCategory()):
        $catSearch = $view['translator']->trans('mautic.core.searchcommand.category') . ":" . $category->getAlias();
        $catName = $category->getTitle();
    else:
        $catSearch = $view['translator']->trans('mautic.core.searchcommand.is') . ":" .
            $view['translator']->trans('mautic.core.searchcommand.isuncategorized');
        $catName = $view['translator']->trans('mautic.core.form.uncategorized');
    endif;
    ?>

    <span class="bundle-main-item-secondary">
        <a href="<?php echo $view['router']->generate('mautic_page_index', array('search' => $catSearch))?>"
           data-toggle="ajax">
            <?php echo $catName; ?>
        </a>
        <span> | </span>
        <span>
            <?php
            $author     = $activePage->getCreatedBy();
            $authorId   = ($author) ? $author->getId() : 0;
            $authorName = ($author) ? $author->getName() : "";
            ?>
            <a href="<?php echo $view['router']->generate('mautic_user_action', array(
                'objectAction' => 'contact',
                'objectId'     => $authorId,
                'entity'       => 'page.page',
                'id'           => $activePage->getId(),
                'returnUrl'    => $view['router']->generate('mautic_page_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $activePage->getId()
                ))
            )); ?>">
                <?php echo $authorName; ?>
            </a>
        </span>
        <span> | </span>
        <span>
            <?php $langSearch = $view['translator']->trans('mautic.page.page.searchcommand.lang').":".$activePage->getLanguage(); ?>
            <a href="<?php echo $view['router']->generate('mautic_page_index', array('search' => $langSearch)); ?>"
               data-toggle="ajax">
                <?php echo $activePage->getLanguage(); ?>
            </a>
        </span>
    </span>

    <?php
    echo $view->render('MauticPageBundle:Page:translations.html.php', array(
        'page' => $activePage,
        'dateFormat' => $dateFormat
    ));

    echo $view->render('MauticPageBundle:Page:variants.html.php', array(
        'page' => $activePage,
        'dateFormat' => $dateFormat
    ));
    ?>

    <div class="form-group margin-md-top">
        <?php if (!empty($variationParent)): ?>
        <label><?php echo $view['translator']->trans('mautic.page.page.urlvariant'); ?></label>
        <?php else: ?>
        <label><?php echo $view['translator']->trans('mautic.page.page.url'); ?></label>
        <?php endif; ?>
        <div class="input-group">
            <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control" readonly
                   value="<?php echo $pageUrl; ?>" />
            <span class="input-group-btn">
                <button class="btn btn-default" onclick="window.open('<?php echo $pageUrl; ?>', '_blank');">
                    <i class="fa fa-external-link"></i>
                </button>
            </span>
        </div>
    </div>
</div>

<h3>@todo - landing page stats/analytics/AB test results will go here</h3>

<div class="footer-margin"></div>
<?php endif;?>