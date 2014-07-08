<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticFormBundle:Form:index.html.php');
}
?>

<?php if (!empty($activeForm)): ?>
<div class="bundle-main-header">
    <span class="bundle-main-item-primary">
<span class="bundle-main-actions">
            <?php
            echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                'item'      => $activeForm,
                'edit'      => $security->hasEntityAccess(
                    $permissions['form:forms:editown'],
                    $permissions['form:forms:editother'],
                    $activeForm->getCreatedBy()
                ),
                'delete'    => $security->hasEntityAccess(
                    $permissions['form:forms:deleteown'],
                    $permissions['form:forms:deleteother'],
                    $activeForm->getCreatedBy()),
                'routeBase' => 'form',
                'menuLink'  => 'mautic_form_index',
                'langVar'   => 'form',
                'custom'    => <<<CUSTOM
<li>
    <a href="{$view['router']->generate('mautic_form_action', array('objectAction' => 'results', 'objectId' => $activeForm->getId()))}"
       data-toggle="ajax"
       data-menu-link="mautic_form_index">
        <span>
            <i class="fa fa-database"></i>{$view['translator']->trans('mautic.form.form.results')}
        </span>
   </a>
</li>
CUSTOM
            ));
            ?>
        </span>
        <?php echo $view['translator']->trans($activeForm->getName()); ?>
    </span>
    <div class="clearfix"></div>
</div>

<?php
    echo $view->render('MauticFormBundle:Form:stats.html.php', array('form' => $activeForm));
    echo $view->render('MauticFormBundle:Form:copy.html.php', array('form' => $activeForm));
?>
<?php endif;?>
<div class="footer-margin"></div>