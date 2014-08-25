<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticFormBundle:Form:index.html.php');
?>

<div class="table-responsive scrollable body-white padding-sm page-list">
    <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered form-list">
            <thead>
            <tr>
                <th class="col-form-actions"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'form',
                    'orderBy'    => 'f.name',
                    'text'       => 'mautic.form.form.thead.name',
                    'class'      => 'col-form-name',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'form',
                    'orderBy'    => 'f.description',
                    'text'       => 'mautic.form.form.thead.description',
                    'class'      => 'visible-md visible-lg col-form-description'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'form',
                    'orderBy'    => 'submissionCount',
                    'text'       => 'mautic.form.form.thead.submissions',
                    'class'      => 'visible-md visible-lg col-form-submissions'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'form',
                    'orderBy'    => 'f.id',
                    'text'       => 'mautic.form.form.thead.id',
                    'class'      => 'visible-md visible-lg col-form-id'
                ));
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                            'item'      => $i[0],
                            'edit'      => $security->hasEntityAccess(
                                $permissions['form:forms:editown'],
                                $permissions['form:forms:editother'],
                                $i[0]->getCreatedBy()
                            ),
                            'clone'     => $permissions['form:forms:create'],
                            'delete'    => $security->hasEntityAccess(
                                $permissions['form:forms:deleteown'],
                                $permissions['form:forms:deleteother'],
                                $i[0]->getCreatedBy()),
                            'routeBase' => 'form',
                            'menuLink'  => 'mautic_form_index',
                            'langVar'   => 'form',
                            'custom'    => <<<CUSTOM
<li>
    <a href="{$view['router']->generate('mautic_form_action', array('objectAction' => 'results', 'objectId' => $i[0]->getId()))}"
       data-toggle="ajax"
       data-menu-link="mautic_form_index">
        <span>
            <i class="fa fa-fw fa-database"></i>{$view['translator']->trans('mautic.form.form.results')}
        </span>
   </a>
</li>
CUSTOM
                        ));
                        ?>
                    </td>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $i[0],
                            'model'      => 'form.form'
                        )); ?>
                        <a href="<?php echo $view['router']->generate('mautic_form_action',
                            array(
                                'objectAction' => 'view',
                                'objectId' => $i[0]->getId()
                            )); ?>"
                           data-toggle="ajax"
                           data-menu-link="mautic_form_index">
                            <?php echo $i[0]->getName() . ' (' . $i[0]->getAlias() . ')'; ?>
                        </a>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $i[0]->getDescription(); ?></td>
                    <td class="visible-md visible-lg">
                        <a href="<?php echo $view['router']->generate('mautic_form_action', array('objectAction' => 'results', 'objectId' => $i[0]->getId())); ?>"
                           data-toggle="ajax"
                           data-menu-link="mautic_form_index">
                            <?php echo $i['submissionCount']; ?>
                        </a>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $i[0]->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
    <?php endif; ?>
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"      => count($items),
        "page"            => $page,
        "limit"           => $limit,
        "baseUrl"         => $view['router']->generate('mautic_form_index'),
        'sessionVar'      => 'form'
    )); ?>
    <div class="footer-margin"></div>
</div>