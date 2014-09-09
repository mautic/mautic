<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'role');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.user.role.header.index'));
$view['slots']->set('searchUri', $this->container->get('router')->generate('mautic_role_index'));
$view['slots']->set('searchString', $app->getSession()->get('mautic.role.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.user.role.help.searchcommands'));

?>

<?php if ($permissions['create']): ?>
    <?php $view['slots']->start("actions"); ?>
	<a class="btn btn-default new-entity-action"
	   href="<?php echo $this->container->get('router')->generate(
	       'mautic_role_action', array("objectAction" => "new")); ?>"
	    data-toggle="ajax" data-menu-link="#mautic_role_index">
	    <i class="fa fa-plus"></i> 
	    <?php echo $view["translator"]->trans("mautic.user.role.menu.new"); ?>
	</a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<?php $view['slots']->output('_content'); ?>