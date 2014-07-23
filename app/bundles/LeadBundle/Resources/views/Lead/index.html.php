<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['blocks']->set('mauticContent', 'lead');
$view['blocks']->set("headerTitle", $view['translator']->trans('mautic.lead.lead.header.index'));
$view['blocks']->set('searchUri', $view['router']->generate('mautic_lead_index', array('page' => $page)));
$view['blocks']->set('searchString', $app->getSession()->get('mautic.lead.filter'));
$view['blocks']->set('searchHelp', $view['translator']->trans('mautic.lead.lead.help.searchcommands'));
?>

<?php if ($permissions['lead:leads:create']): ?>
    <?php $view['blocks']->start("actions"); ?>
    <li>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_lead_action', array("objectAction" => "new")); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_lead_index">
            <?php echo $view["translator"]->trans("mautic.lead.lead.menu.new"); ?>
        </a>
    </li>
    <?php $view['blocks']->stop(); ?>
<?php endif; ?>

<?php $view['blocks']->start('toolbar'); ?>
<div class="btn-group">
    <a href="<?php echo $view['router']->generate('mautic_lead_index', array('page' => $page, 'view' => 'list')); ?>"
       data-toggle="ajax"
       class="btn btn-default"><i class="fa fa-fw fa-list"></i></a>
    <a href="<?php echo $view['router']->generate('mautic_lead_index', array('page' => $page, 'view' => 'grid')); ?>"
       data-toggle="ajax"
       class="btn btn-default"><i class="fa fa-fw fa-th-large"></i></a>
</div>
<?php $view['blocks']->stop(); ?>

<?php $view['blocks']->output('_content'); ?>