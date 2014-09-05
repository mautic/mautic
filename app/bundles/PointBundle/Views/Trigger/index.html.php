<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'pointTrigger');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.point.trigger.header.index'));
$view['slots']->set('searchUri', $view['router']->generate('mautic_pointtrigger_index', array('page' => $page)));
$view['slots']->set('searchString', $app->getSession()->get('mautic.point.trigger.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.core.help.searchcommands'));
?>

<?php if ($permissions['point:triggers:create']): ?>
    <?php $view['slots']->start("actions"); ?>
    <li>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_pointtrigger_action', array("objectAction" => "new")); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_pointtrigger_index">
            <?php echo $view["translator"]->trans("mautic.point.trigger.menu.new"); ?>
        </a>
    </li>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<?php $view['slots']->output('_content'); ?>
