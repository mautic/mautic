<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'point');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.points.menu.root'));
$view['slots']->set('searchUri', $view['router']->generate('mautic_point_index', array('page' => $page)));
$view['slots']->set('searchString', $app->getSession()->get('mautic.point.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.core.help.searchcommands'));
?>

<?php if ($permissions['point:points:create']): ?>
    <?php $view['slots']->start("actions"); ?>
    <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
        'mautic_point_action', array("objectAction" => "new")); ?>"
        data-toggle="ajax"
        data-menu-link="#mautic_point_index">
        <i class="fa fa-plus"></i> 
        <?php echo $view["translator"]->trans("mautic.point.menu.new"); ?>
    </a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<?php $view['slots']->output('_content'); ?>
