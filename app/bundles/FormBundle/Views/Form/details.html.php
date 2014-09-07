<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'form');
$view['slots']->set("headerTitle", $activeForm->getName());
?>

<?php $view['slots']->start("actions"); ?>
  <a class="btn btn-default" href="<?php echo $view['router']->generate('mautic_form_action', array(
        'objectAction' => 'results', 'objectId' => $activeForm->getId())); ?>"
       data-toggle="ajax"
       data-menu-link="mautic_form_index">
        <span>
            <i class="fa fa-fw fa-database"></i> <?php echo $view['translator']->trans('mautic.form.form.results'); ?>
        </span>
    </a>
    <a class="btn btn-default" data-toggle="modal" data-target="#form-preview">
        <i class="fa fa-fw fa-camera"></i> <?php echo $view['translator']->trans('mautic.form.form.preview'); ?>
    </a>
<?php if ($security->hasEntityAccess($permissions['form:forms:editown'], $permissions['form:forms:editother'],
    $activeForm->getCreatedBy())): ?>
        <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
            'mautic_form_action', array("objectAction" => "edit", "objectId" => $activeForm->getId())); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_form_index">
            <i class="fa fa-fw fa-pencil-square-o"></i><?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
        </a>
<?php endif; ?>
<?php if ($security->hasEntityAccess($permissions['form:forms:deleteown'], $permissions['form:forms:deleteother'],
    $activeForm->getCreatedBy())): ?>
    <a href="javascript:void(0);" class="btn btn-default"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic.form.form.confirmdelete",
           array("%name%" => $activeForm->getName() . " (" . $activeForm->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_form_action',
           array("objectAction" => "delete", "objectId" => $activeForm->getId())); ?>',
           '#mautic_form_index'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <span><i class="fa fa-fw fa-trash-o text-danger"></i> <?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
    </a>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<div class="scrollable form-details">
    <?php
        echo $view->render('MauticFormBundle:Form:stats.html.php', array('form' => $activeForm));
        echo $view->render('MauticFormBundle:Form:copy.html.php', array('form' => $activeForm));
    ?>
    <div class="footer-margin"></div>
</div>