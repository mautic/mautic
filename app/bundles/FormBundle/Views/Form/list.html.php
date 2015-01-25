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
                    'text'       => 'mautic.core.name',
                    'class'      => 'col-form-name',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'form',
                    'orderBy'    => 'c.title',
                    'text'       => 'mautic.core.category',
                    'class'      => 'visible-md visible-lg col-form-category'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'form',
                    'orderBy'    => 'submissionCount',
                    'text'       => 'mautic.form.form.results',
                    'class'      => 'visible-md visible-lg col-form-submissions'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'form',
                    'orderBy'    => 'f.id',
                    'text'       => 'mautic.core.id',
                    'class'      => 'visible-md visible-lg col-form-id'
                ));
                ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i): ?>
        <?php $item = $i[0]; ?>
            <tr>
                <td class="visible-md visible-lg">
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', array(
                        'item'      => $item,
                        'templateButtons' => array(
                            'edit'      => $security->hasEntityAccess($permissions['form:forms:editown'], $permissions['form:forms:editother'], $item->getCreatedBy()),
                            'clone'     => $permissions['form:forms:create'],
                            'delete'    => $security->hasEntityAccess($permissions['form:forms:deleteown'], $permissions['form:forms:deleteother'], $item->getCreatedBy()),
                        ),
                        'routeBase' => 'form',
                        'customButtons'    => array(
                            array(
                                'attr' => array(
                                    'data-toggle' => '',
                                    'target'      => '_blank',
                                    'href'        => $view['router']->generate('mautic_form_action', array('objectAction' => 'preview', 'objectId' => $item->getId())),
                                ),
                                'iconClass' => 'fa fa-camera',
                                'btnText'   => 'mautic.form.form.preview'
                            ),
                            array(
                                'attr' => array(
                                    'data-toggle' => 'ajax',
                                    'href'        => $view['router']->generate('mautic_form_action', array('objectAction' => 'results', 'objectId' => $item->getId())),
                                ),
                                'iconClass' => 'fa fa-database',
                                'btnText'   => 'mautic.form.form.results'
                            )
                        )
                    ));
                    ?>
                </td>
                <td>
                    <div>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php',array(
                            'item'       => $item,
                            'model'      => 'form.form'
                        )); ?>
                        <a href="<?php echo $view['router']->generate('mautic_form_action', array('objectAction' => 'view', 'objectId' => $item->getId())); ?>" data-toggle="ajax" data-menu-link="mautic_form_index">
                            <?php echo $item->getName() . ' (' . $item->getAlias() . ')'; ?>
                        </a>
                    </div>
                    <?php if ($description = $item->getDescription()): ?>
                        <div class="text-muted mt-4"><small><?php echo $description; ?></small></div>
                    <?php endif; ?>
                </td>
                <td class="visible-md visible-lg">
                    <?php $category = $item->getCategory(); ?>
                    <?php $catName  = ($category) ? $category->getTitle() : $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                    <?php $color    = ($category) ? '#' . $category->getColor() : 'inherit'; ?>
                    <span class="label label-default pa-5" style="background: <?php echo $color; ?>;"> </span>
                    <span><?php echo $catName; ?></span>
                </td>
                <td class="visible-md visible-lg">
                    <a href="<?php echo $view['router']->generate('mautic_form_action', array('objectAction' => 'results', 'objectId' => $item->getId())); ?>" data-toggle="ajax" data-menu-link="mautic_form_index"><?php echo $i['submissionCount']; ?></a>
                </td>
                <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
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
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', array('tip' => 'mautic.form.noresults.tip')); ?>
<?php endif; ?>
