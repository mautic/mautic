<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'report');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.report.report.header.index'));
$view['slots']->set('searchUri', $view['router']->generate('mautic_report_index', array('page' => $page)));
$view['slots']->set('searchString', $app->getSession()->get('mautic.report.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.report.report.help.searchcommands'));
?>

<?php if ($permissions['report:reports:create']): ?>
    <?php $view['slots']->start("actions"); ?>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_report_action', array("objectAction" => "new")); ?>"
            data-toggle="ajax"
            class="btn btn-default"
            data-menu-link="#mautic_report_index">
            	<i class="fa fa-plus"></i> 
            	<?php echo $view["translator"]->trans("mautic.report.report.menu.new"); ?>
        </a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<?php $view['slots']->output('_content'); ?>
