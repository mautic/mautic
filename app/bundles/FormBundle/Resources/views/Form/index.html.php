<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['blocks']->set('mauticContent', 'form');
$view['blocks']->set("headerTitle", $view['translator']->trans('mautic.form.form.header.index'));
$view['blocks']->set('searchUri', $view['router']->generate('mautic_form_index', array('page' => $page)));
$view['blocks']->set('searchString', $app->getSession()->get('mautic.form.filter'));
$view['blocks']->set('searchHelp', $view['translator']->trans('mautic.form.form.help.searchcommands'));
?>

<?php if ($permissions['form:forms:create']): ?>
    <?php $view['blocks']->start("actions"); ?>
    <li>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_form_action', array("objectAction" => "new")); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_form_index">
            <?php echo $view["translator"]->trans("mautic.form.form.menu.new"); ?>
        </a>
    </li>
    <?php $view['blocks']->stop(); ?>
<?php endif; ?>

<?php $view['blocks']->output('_content'); ?>