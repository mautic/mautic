<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'asset');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.asset.asset.menu.root'));
?>

<?php if ($permissions['asset:assets:create']): ?>
    <?php $view['slots']->start("actions"); ?>
    <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
        'mautic_asset_action', array("objectAction" => "new")); ?>"
       data-toggle="ajax"
       data-menu-link="#mautic_asset_index">
        <i class="fa fa-plus"></i>
        <?php echo $view["translator"]->trans("mautic.asset.asset.menu.new"); ?>
    </a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render('MauticCoreBundle:Helper:listactions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'menuLink'    => 'mautic_asset_index',
        'langVar'     => 'asset.asset',
        'routeBase'   => 'asset',
        'delete'      => $permissions['asset:assets:deleteown'] || $permissions['asset:assets:deleteother']
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
