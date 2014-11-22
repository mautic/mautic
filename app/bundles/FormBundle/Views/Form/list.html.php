<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticFormBundle:Form:index.html.php');
}
?>
<?php if (count($items)): ?>
<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered" id="formTable">
        <thead>
            <tr>
                <th class="visible-md visible-lg col-form-actions pl-20">
                    <div class="checkbox-inline custom-primary">
                        <label class="mb-0 pl-10">
                            <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#formTable">
                            <span></span>
                        </label>
                    </div>
                </th>
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
                    'orderBy'    => 'c.title',
                    'text'       => 'mautic.form.form.thead.category',
                    'class'      => 'visible-md visible-lg col-form-category'
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
                <td class="visible-md visible-lg">
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
                        'custom'    => array(
                            array(
                                'attr' => array(
                                    'data-toggle' => 'ajax',
                                    'href'        => $view['router']->generate('mautic_form_action', array(
                                        'objectAction' => 'results',
                                        'objectId' => $i[0]->getId()
                                    )),
                                ),
                                'icon' => 'fa-database',
                                'label' => 'mautic.form.form.results'
                            )
                        )
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
                    <?php $catName = ($category = $i[0]->getCategory()) ? $category->getTitle() :
                        $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                    <span><?php echo $catName; ?></span>
                </td>
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
    <div class="panel-footer">
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"      => $totalItems,
        "page"            => $page,
        "limit"           => $limit,
        "baseUrl"         => $view['router']->generate('mautic_form_index'),
        'sessionVar'      => 'form'
    )); ?>
    </div>
</div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
