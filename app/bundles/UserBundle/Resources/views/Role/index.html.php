<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['blocks']->set('mauticContent', 'role');
$view['blocks']->set('headerTitle', $view['translator']->trans('mautic.user.role.header.index'));
$view['blocks']->set('searchUri', $this->container->get('router')->generate('mautic_role_index'));
$view['blocks']->set('searchString', $app->getSession()->get('mautic.role.filter'));
$view['blocks']->set('searchHelp', $view['translator']->trans('mautic.user.role.help.searchcommands'));

?>

<?php if ($permissions['create']): ?>
    <?php $view['blocks']->start("actions"); ?>
    <li><a class="new-entity-action"
           href="<?php echo $this->container->get('router')->generate(
               'mautic_role_action', array("objectAction" => "new")); ?>"
            data-toggle="ajax" data-menu-link="#mautic_role_index">
            <?php echo $view["translator"]->trans("mautic.user.role.menu.new"); ?>
        </a>
    </li>
    <?php $view['blocks']->stop(); ?>
<?php endif; ?>

<?php $view['blocks']->output('_content'); ?>