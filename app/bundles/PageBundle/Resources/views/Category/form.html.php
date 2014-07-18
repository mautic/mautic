<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'pagecategory');

$objectId = $form->vars['data']->getId();
if (!empty($objectId)) {
    $name   = $form->vars['data']->getTitle();
    $header = $view['translator']->trans('mautic.page.category.header.edit', array("%name%" => $name));
} else {
    $header = $view['translator']->trans('mautic.page.category.header.new');
}
$view["slots"]->set("headerTitle", $header);
?>

<div class="scrollable">
    <?php echo $view['form']->form($form); ?>
    <div class="footer-margin"></div>
</div>