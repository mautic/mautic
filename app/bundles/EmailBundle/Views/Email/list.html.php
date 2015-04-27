<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticEmailBundle:Email:index.html.php');
}
?>

<?php if (count($items)): ?>
<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered email-list">
        <thead>
        <tr>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'checkall' => 'true',
                'target'   => '.email-list'
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'email',
                'orderBy'    => 'e.subject',
                'text'       => 'mautic.email.subject',
                'class'      => 'col-email-subject',
                'default'    => true
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'email',
                'orderBy'    => 'c.title',
                'text'       => 'mautic.core.category',
                'class'      => 'visible-md visible-lg col-email-category'
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'email',
                'orderBy'    => 'e.sentCount',
                'text'       => 'mautic.email.thead.sentcount',
                'class'      => 'visible-md visible-lg col-email-sentcount'
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'email',
                'orderBy'    => 'e.readCount',
                'text'       => 'mautic.email.thead.readcount',
                'class'      => 'visible-md visible-lg col-email-readcount'
            )); ?>

            <th class="visible-md visible-lg col-email-pending"><?php echo $view['translator']->trans('mautic.email.thead.leadcount'); ?></th>

            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'email',
                'orderBy'    => 'e.id',
                'text'       => 'mautic.core.id',
                'class'      => 'visible-md visible-lg col-email-id'
            ));
            ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <?php
            $variantChildren = $item->getVariantChildren();
            $hasVariants     = count($variantChildren);
            ?>
            <tr>
                <td>
                    <?php
                    $edit = $security->hasEntityAccess($permissions['email:emails:editown'], $permissions['email:emails:editother'], $item->getCreatedBy());
                    echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', array(
                        'item'       => $item,
                        'templateButtons' => array(
                            'edit'       => $edit,
                            'clone'      => $permissions['email:emails:create'],
                            'delete'     => $security->hasEntityAccess($permissions['email:emails:deleteown'], $permissions['email:emails:deleteother'], $item->getCreatedBy()),
                            'abtest'     => (!$hasVariants && $edit && $permissions['email:emails:create']),
                        ),
                        'routeBase'  => 'email',
                        'nameGetter' => 'getSubject',
                        'customButtons' => array(
                            array(
                                'confirm' => array(
                                    'message'       => $view["translator"]->trans("mautic.email.form.confirmsend", array("%name%" => $item->getSubject() . " (" . $item->getId() . ")")),
                                    'confirmText'   => $view["translator"]->trans("mautic.email.send"),
                                    'confirmAction' => $view['router']->generate('mautic_email_action', array("objectAction" => "send", "objectId" => $item->getId())),
                                    'iconClass'     => 'fa fa-send-o',
                                    'btnText'       => $view["translator"]->trans("mautic.email.send")
                                )
                            )
                        )
                    ));
                    ?>
                </td>
                <td>
                    <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php',array('item' => $item, 'model' => 'email')); ?>
                    <a href="<?php echo $view['router']->generate('mautic_email_action', array("objectAction" => "view", "objectId" => $item->getId())); ?>" data-toggle="ajax">
                        <?php echo $item->getSubject(); ?>
                        <?php if ($hasVariants): ?>
                        <span><i class="fa fa-fw fa-sitemap"></i></span>
                        <?php endif; ?>
                    </a>
                </td>
                <td class="visible-md visible-lg">
                    <?php $category = $item->getCategory(); ?>
                    <?php $catName  = ($category) ? $category->getTitle() : $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                    <?php $color    = ($category) ? '#' . $category->getColor() : 'inherit'; ?>
                    <span style="white-space: nowrap;"><span class="label label-default pa-4" style="border: 1px solid #d5d5d5; background: <?php echo $color; ?>;"> </span> <span><?php echo $catName; ?></span></span>
                </td>
                <td class="visible-md visible-lg"><?php echo $item->getSentCount(); ?></td>
                <td class="visible-md visible-lg"><?php echo $item->getReadCount(); ?></td>
                <td class="visible-md visible-lg"><?php echo $model->getPendingLeads($item, null, true); ?></td>
                <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="panel-footer">
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"      => $totalItems,
        "page"            => $page,
        "limit"           => $limit,
        "baseUrl"         => $view['router']->generate('mautic_email_index'),
        'sessionVar'      => 'email'
    )); ?>
</div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
