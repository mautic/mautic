<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'email');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.email.header.index'));
?>

<?php if ($permissions['email:emails:create']): ?>
    <?php $view['slots']->start("actions"); ?>
    <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
        'mautic_email_action', array("objectAction" => "new")); ?>"
       data-toggle="ajax"
       data-menu-link="#mautic_email_index">
        <i class="fa fa-plus"></i>
        <?php echo $view["translator"]->trans("mautic.email.menu.new"); ?>
    </a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<div class="box-layout">
	<!-- filters -->
    <?php echo $view->render('MauticEmailBundle:Email:filters.html.php', array('filters' => $filters)); ?>
    <!--/ filters -->

    <?php $view['slots']->output('_content'); ?>
</div>

