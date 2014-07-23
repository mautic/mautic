<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['blocks']->set('mauticContent', 'leadlist');
$view['blocks']->set("headerTitle", $view['translator']->trans('mautic.lead.list.header.index'));
$view['blocks']->set('searchUri', $this->container->get('router')->generate('mautic_leadlist_index'));
$view['blocks']->set('searchString', $app->getSession()->get('mautic.leadlist.filter'));
$view['blocks']->set('searchHelp', $view['translator']->trans('mautic.lead.list.help.searchcommands'));
?>
<?php $view['blocks']->start("actions"); ?>
<li><a href="<?php echo $this->container->get('router')->generate(
        'mautic_leadlist_action', array("objectAction" => "new")); ?>" data-toggle="ajax">
        <?php echo $view["translator"]->trans("mautic.lead.list.menu.new"); ?>
    </a>
</li>
<?php $view['blocks']->stop(); ?>
<?php $view['blocks']->output('_content'); ?>