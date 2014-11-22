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
<div class="col-md-9 bg-auto height-auto bdr-l table-responsive pt-10">
    <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered email-list">
            <thead>
            <tr>
                <th class="col-email-actions"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'email',
                    'orderBy'    => 'e.subject',
                    'text'       => 'mautic.email.thead.subject',
                    'class'      => 'col-email-subject',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'email',
                    'orderBy'    => 'c.title',
                    'text'       => 'mautic.email.thead.category',
                    'class'      => 'visible-md visible-lg col-email-category'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'email',
                    'orderBy'    => 'e.createdByUser',
                    'text'       => 'mautic.email.thead.author',
                    'class'      => 'visible-md visible-lg col-email-author'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'email',
                    'orderBy'    => 'e.language',
                    'text'       => 'mautic.email.thead.language',
                    'class'      => 'visible-md visible-lg col-email-lang'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'email',
                    'orderBy'    => 'e.sendCount',
                    'text'       => 'mautic.email.thead.sentcount',
                    'class'      => 'col-email-sendcount'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'email',
                    'orderBy'    => 'e.readCount',
                    'text'       => 'mautic.email.thead.readcount',
                    'class'      => 'col-email-readcount'
                )); ?>
                <td><?php echo $view['translator']->trans('mautic.email.thead.listcount'); ?></td>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'email',
                    'orderBy'    => 'e.id',
                    'text'       => 'mautic.email.thead.id',
                    'class'      => 'col-email-id'
                ));
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $variantChildren = $item->getVariantChildren();
                ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                            'item'      => $item,
                            'edit'      => $security->hasEntityAccess(
                                $permissions['email:emails:editown'],
                                $permissions['email:emails:editother'],
                                $item->getCreatedBy()
                            ),
                            'clone'     => $permissions['email:emails:create'],
                            'delete'    => $security->hasEntityAccess(
                                $permissions['email:emails:deleteown'],
                                $permissions['email:emails:deleteother'],
                                $item->getCreatedBy()),
                            'routeBase' => 'email',
                            'menuLink'  => 'mautic_email_index',
                            'langVar'   => 'email',
                            'nameGetter' => 'getSubject',
                            'custom'    => array(
                                array(
                                    'attr' => array(
                                        'href'   => 'javascript: void(0);',
                                        'onclick' =>
                                            "Mautic.showConfirmation(
                                           '{$view->escape($view["translator"]->trans("mautic.email.form.confirmsend",
                                                array("%name%" => $item->getSubject() . " (" . $item->getId() . ")")), 'js')}',
                                           '{$view->escape($view["translator"]->trans("mautic.email.send"), 'js')}',
                                           'executeAction',
                                           ['{$view['router']->generate('mautic_email_action',
                                                array("objectAction" => "send", "objectId" => $item->getId()))}',
                                           '#mautic_email_index'],
                                           '{$view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js')}','',[]);"
                                    ),
                                    'icon' => 'fa-send',
                                    'label' => 'mautic.email.sendmanual'
                                )
                            )
                        ));
                        ?>
                    </td>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $item,
                            'model'      => 'email'
                        )); ?>
                        <a href="<?php echo $view['router']->generate('mautic_email_action',
                            array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                           data-toggle="ajax">
                            <?php echo $item->getSubject(); ?>
                        </a>
                        <?php
                        $hasVariants = count($variantChildren);
                        if ($hasVariants): ?>
                        <span>
                            <i class="fa fa-fw fa-sitemap"></i>
                        </span>
                    <?php endif; ?>
                    </td>
                    <td class="visible-md visible-lg">
                        <?php $catName = ($category = $item->getCategory()) ? $category->getTitle() :
                            $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                        <span><?php echo $catName; ?></span>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getCreatedByUser(); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getLanguage(); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getSentCount(); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getReadCount(); ?></td>
                    <td class="visible-md visible-lg"><?php echo count($item->getLists()); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
    <?php endif; ?>
</div>
