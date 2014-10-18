<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'leadlist');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.lead.list.header.index'));
$view['slots']->set('searchUri', $this->container->get('router')->generate('mautic_leadlist_index'));
$view['slots']->set('searchString', $app->getSession()->get('mautic.leadlist.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.lead.list.help.searchcommands'));
?>
<?php $view['slots']->start("title"); ?>
  <h3><?php echo $view['translator']->trans('mautic.lead.list.header.index'); ?></h3>
<?php $view['slots']->stop(); ?>

<?php $view['slots']->start("actions"); ?>
<a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
        'mautic_leadlist_action', array("objectAction" => "new")); ?>" data-toggle="ajax">
        <i class="fa fa-plus"></i> 
        <?php echo $view["translator"]->trans("mautic.lead.list.menu.new"); ?>
</a>
<?php $view['slots']->stop(); ?>
<?php $view['slots']->output('_content'); ?>