<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'user');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.user.user.header.index'));
$view['slots']->set('searchUri', $this->container->get('router')->generate('mautic_user_index'));
$view['slots']->set('searchString', $app->getSession()->get('mautic.user.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.user.user.help.searchcommands'));
?>

<?php if ($permissions['create']): ?>
<?php $view['slots']->start("actions"); ?>
<li>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_user_action', array("objectAction" => "new")); ?>"
    data-toggle="ajax"
    data-menu-link="#mautic_user_index">
        <?php echo $view["translator"]->trans("mautic.user.user.menu.new"); ?>
    </a>
</li>
<?php $view['slots']->stop(); ?>
<?php endif; ?>

<?php $view['slots']->output('_content'); ?>
