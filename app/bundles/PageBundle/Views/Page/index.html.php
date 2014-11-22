<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'page');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.page.header.index'));
?>

<?php if ($permissions['page:pages:create']): ?>
    <?php $view['slots']->start("actions"); ?>
        <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
            'mautic_page_action', array("objectAction" => "new")); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_page_index">
           <i class="fa fa-plus"></i>
            <?php echo $view["translator"]->trans("mautic.page.menu.new"); ?>
        </a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render('MauticCoreBundle:Helper:listactions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'menuLink'    => 'mautic_page_index',
        'langVar'     => 'page.page',
        'routeBase'   => 'page',
        'delete'      => $permissions['page:pages:deleteown'] || $permissions['page:pages:deleteother']
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
