<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo - add category wide stats/analytics

if ($tmpl == 'index') {
    $view->extend('MauticPageBundle:Category:index.html.php');
}
?>

<?php if (!empty($activeCategory)): ?>
<div class="bundle-main-header">
    <span class="bundle-main-item-primary">
        <?php echo $view['translator']->trans($activeCategory->getTitle()); ?>
        <span class="bundle-main-actions">
            <?php
            echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                'item'       => $activeCategory,
                'edit'       => $permissions['page:categories:edit'],
                'delete'     => $permissions['page:categories:delete'],
                'routeBase'  => 'pagecategory',
                'menuLink'   => 'mautic_pagecategory_index',
                'langVar'    => 'page.category',
                'nameGetter' => 'getTitle'
            ));
            ?>
        </span>
    </span>
</div>

<h3>@todo - category wide stats will go here</h3>

<div class="clearfix"></div>
<?php endif;?>