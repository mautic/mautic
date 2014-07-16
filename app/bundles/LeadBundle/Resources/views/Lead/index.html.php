<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'lead');
$view["slots"]->set("headerTitle", $view['translator']->trans('mautic.lead.lead.header.index'));
$view['slots']->set('searchUri', $view['router']->generate('mautic_lead_index', array('page' => $page, 'tmpl' => $tmpl)));
$view['slots']->set('searchString', $app->getSession()->get('mautic.lead.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.lead.list.help.searchcommands'));
?>

<?php if ($permissions['lead:leads:create']): ?>
    <?php $view["slots"]->start("actions"); ?>
    <li>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_lead_action', array("objectAction" => "new")); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_lead_index">
            <?php echo $view["translator"]->trans("mautic.lead.lead.menu.new"); ?>
        </a>
    </li>
    <?php $view["slots"]->stop(); ?>
<?php endif; ?>

<?php $view['slots']->start('toolbar'); ?>
<div class="btn-group">
    <a href="<?php echo $view['router']->generate('mautic_lead_index', array('page' => $page, 'view' => 'list')); ?>"
       data-toggle="ajax"
       class="btn btn-default"><i class="fa fa-fw fa-list"></i></a>
    <a href="<?php echo $view['router']->generate('mautic_lead_index', array('page' => $page, 'view' => 'grid')); ?>"
       data-toggle="ajax"
       class="btn btn-default"><i class="fa fa-fw fa-th-large"></i></a>
</div>
<?php $view['slots']->stop(); ?>

<?php $view['slots']->output('_content'); ?>