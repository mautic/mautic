<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'asset');

$subheader = '';

$header = ($activeAsset->getId()) ?
    $view['translator']->trans('mautic.asset.asset.header.edit',
        array('%name%' => $activeAsset->getTitle())) :
    $view['translator']->trans('mautic.asset.asset.header.new');

$view['slots']->set("headerTitle", $header.$subheader);
?>

<div class="scrollable">
    <?php echo $view['form']->form($form); ?>
    <div class="footer-margin"></div>
</div>