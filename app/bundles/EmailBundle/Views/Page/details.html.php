<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo - add landing page stats/analytics
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'page');
$view['slots']->set("headerTitle", $activePage->getTitle());?>

<?php
$view['slots']->start('actions');
if ($security->hasEntityAccess($permissions['page:pages:editown'], $permissions['page:pages:editother'],
    $activePage->getCreatedBy())): ?>
    <li>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_page_action', array("objectAction" => "edit", "objectId" => $activePage->getId())); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_page_index">
            <i class="fa fa-fw fa-pencil-square-o"></i><?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
        </a>
    </li>
<?php endif; ?>
<?php if ($security->hasEntityAccess($permissions['page:pages:deleteown'], $permissions['page:pages:deleteother'],
    $activePage->getCreatedBy())): ?>
    <li>
        <a href="javascript:void(0);"
           onclick="Mautic.showConfirmation(
               '<?php echo $view->escape($view["translator"]->trans("mautic.page.page.confirmdelete",
               array("%name%" => $activePage->getTitle() . " (" . $activePage->getId() . ")")), 'js'); ?>',
               '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
               'executeAction',
               ['<?php echo $view['router']->generate('mautic_page_action',
               array("objectAction" => "delete", "objectId" => $activePage->getId())); ?>',
               '#mautic_page_index'],
               '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
            <span><i class="fa fa-fw fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
        </a>
    </li>
<?php endif; ?>
<?php if (empty($variants['parent']) && $permissions['page:pages:create']): ?>
    <li>
        <a href="<?php echo $view['router']->generate('mautic_page_action',
           array("objectAction" => "abtest", "objectId" => $activePage->getId())); ?>"
        data-toggle="ajax"
        data-menu-link="mautic_page_index">
        <span><i class="fa fa-sitemap"></i><?php echo $view['translator']->trans('mautic.page.page.form.abtest'); ?></span>
        </a>
    </li>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<div class="scrollable">
    <div class="bundle-main-header">
        <span class="bundle-main-item-primary">
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
    </div>

    <div class="form-group margin-md-top">
        <?php if (!empty($variants['parent'])): ?>
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

    <h3>@todo - landing page stats/analytics/AB test results will go here</h3>
    <?php echo "<pre>".print_r($stats, true)."</pre>"; ?>

    <?php
    echo $view->render('MauticPageBundle:Page:translations.html.php', array(
        'page'         => $activePage,
        'translations' => $translations
    ));
    ?>

    <?php if (!empty($variants['parent']) || !empty($variants['children'])): ?>
    <?php echo $view->render('MauticPageBundle:AbTest:details.html.php', array(
        'page'          => $activePage,
        'abTestResults' => $abTestResults,
        'variants'      => $variants
    )); ?>
    <?php endif; ?>
    <div class="footer-margin"></div>
</div>
