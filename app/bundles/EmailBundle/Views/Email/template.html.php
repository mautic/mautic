<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (isset($template)) {
    // Ajax update
    extract($template);
}
?>

<?php if (count($items)): ?>
<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered email-template">
        <thead>
        <tr>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'checkall' => 'true',
                'target'   => '.email-template',
                'tmpl'     => 'template'
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'email',
                'orderBy'    => 'e.name',
                'text'       => 'mautic.core.name',
                'class'      => 'col-email-name',
                'default'    => true,
                'tmpl'       => 'template'
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'email',
                'orderBy'    => 'c.title',
                'text'       => 'mautic.core.category',
                'class'      => 'visible-md visible-lg col-email-category',
                'tmpl'       => 'template'
            ));
            ?>

            <th class="visible-sm visible-md visible-lg col-email-stats"><?php echo $view['translator']->trans('mautic.email.thead.stats'); ?></th>

            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'email',
                'orderBy'    => 'e.id',
                'text'       => 'mautic.core.id',
                'class'      => 'visible-md visible-lg col-email-id',
                'tmpl'       => 'template'
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
                    $edit = (!$item->getSentCount()) && $security->hasEntityAccess($permissions['email:emails:editown'], $permissions['email:emails:editother'], $item->getCreatedBy());
                    echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', array(
                        'item'       => $item,
                        'templateButtons' => array(
                            'edit'       => $edit,
                            'clone'      => $permissions['email:emails:create'],
                            'delete'     => $security->hasEntityAccess($permissions['email:emails:deleteown'], $permissions['email:emails:deleteother'], $item->getCreatedBy()),
                            'abtest'     => (!$hasVariants && $edit && $permissions['email:emails:create']),
                        ),
                        'routeBase'  => 'email'
                    ));
                    ?>
                </td>
                <td>
                    <div>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php',array('item' => $item, 'model' => 'email')); ?>
                        <a href="<?php echo $view['router']->generate('mautic_email_action', array("objectAction" => "view", "objectId" => $item->getId())); ?>" data-toggle="ajax">
                            <?php echo $item->getName(); ?>
                            <?php if ($hasVariants): ?>
                            <span><i class="fa fa-fw fa-sitemap"></i></span>
                            <?php endif; ?>
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
                    <span style="white-space: nowrap;"><span class="label label-default pa-4" style="border: 1px solid #d5d5d5; background: <?php echo $color; ?>;"> </span> <span><?php echo $catName; ?></span></span>
                </td>
                <td class="visible-sm visible-md visible-lg col-stats">
                    <span class="mt-xs label label-warning"><?php echo $view['translator']->trans('mautic.email.stat.sentcount', array('%count%' => $item->getSentCount())); ?></span>
                    <span class="mt-xs label label-success"><?php echo $view['translator']->trans('mautic.email.stat.readcount', array('%count%' => $item->getReadCount())); ?></span>
                </td>
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
        'sessionVar'      => 'email.template',
        'tmpl'            => 'template'
    )); ?>
</div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
