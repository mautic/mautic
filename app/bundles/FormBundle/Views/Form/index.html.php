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
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.form.form.header.index'));
$view['slots']->set('searchUri', $view['router']->generate('mautic_form_index', array('page' => $page)));
$view['slots']->set('searchString', $app->getSession()->get('mautic.form.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.form.form.help.searchcommands'));
?>

<?php if ($permissions['form:forms:create']): ?>
    <?php $view['slots']->start("actions"); ?>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_form_action', array("objectAction" => "new")); ?>"
           data-toggle="ajax"
           class="btn btn-default"
           data-menu-link="#mautic_form_index">
            <i class="fa fa-pencil"></i><?php // echo $view["translator"]->trans("mautic.form.form.menu.new"); ?>
        </a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<?php $view['slots']->output('_content'); ?>