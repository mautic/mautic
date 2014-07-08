<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticPageBundle:Category:index.html.php');
}
?>

<div class="bundle-main-header">
    <div class="bundle-main-item-primary">
        <?php
        $header = ($activeCategory->getId()) ?
            $view['translator']->trans('mautic.page.category.header.edit',
                array('%name%' => $activeCategory->getTitle())) :
            $view['translator']->trans('mautic.page.category.header.new');
        echo $header;
        ?>
    </div>
</div>

<?php echo $view['form']->form($form); ?>
<div class="footer-margin"></div>