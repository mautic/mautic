<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
$view->extend('MauticEmailBundle:Email:index.html.php');
?>

<!-- reset container-fluid padding -->
<div class="mna-md">
    <!-- start: box layout -->
    <div class="box-layout">
        <div class="col-md-3 bg-white height-auto">
            <div class="pr-lg pl-lg pt-md pb-md">
                <a href="#" class="btn btn-primary btn-block"><i class="fa fa-edit"></i> Compose Email</a>
                <hr />
                <div class="list-group">
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-inbox"></i> Inbox <span class="semibold text-muted pull-right">1943</span></a>
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-paper-plane"></i> Sent <span class="semibold text-muted pull-right">51</span></a>
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-pencil"></i> Draft <span class="semibold text-muted pull-right">11</span></a>
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-trash-o"></i> Trash</a>
                </div>
                <h5 class="pb-10">Category</h5>
                <div class="list-group">
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-square text-danger mr5"></i> Work</a>
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-square text-muted mr5"></i> Design</a>
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-square text-teal mr5"></i> Social</a>
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-square text-success mr5"></i> Magazine</a>
                </div>
                <h5 class="pb-10">Lists</h5>
                <div class="list-group">
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-square text-muted mr5"></i> List A</a>
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-square text-muted mr5"></i> List B</a>
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-square text-muted mr5"></i> List C</a>
                    <a href="javascript:void(0);" class="list-group-item"><i class="fa fa-square text-muted mr5"></i> List D</a>
                </div>
            </div>
        </div>

        <!-- container -->
        <div class="col-md-9 bg-auto height-auto bdr-l">
            <form novalidate="" autocomplete="off" data-toggle="ajax" role="form" name="lead" method="post" action="/mautic/index_dev.php/leads/edit/50" class="tab-content">                <!-- pane -->
                <div class="tab-pane fade in active bdr-rds-0 bdr-w-0" id="core">
                    <div class="pa-md bg-auto bg-light-xs bdr-b">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default"><i class="fa fa-refresh"></i></button>
                                    <button type="button" class="btn btn-default"><i class="fa fa-trash-o"></i></button>
                                </div>
                                <div class="btn-group hidden-xs">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <i class="fa fa-folder-o"></i>&nbsp;&nbsp;<span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                        <li role="presentation" class="dropdown-header">Move to folder</li>
                                        <li><a href="javascript:void(0);">Important</a></li>
                                        <li><a href="javascript:void(0);">Misc</a></li>
                                        <li><a href="javascript:void(0);">Work</a></li>
                                    </ul>
                                </div>
                                <div class="pull-right">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default"><i class="fa fa-angle-left"></i></button>
                                        <button type="button" class="btn btn-default"><i class="fa fa-angle-right"></i></button>
                                    </div>
                                </div>
                    </div>
                    <div class="pa-md">
                            <div class="table-responsive scrollable body-white padding-sm page-list">
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
                                                'orderBy'    => 'e.author',
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
                                                                    'onlick' =>
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
                                                    <?php $catName = ($category = $item->getCategory()) ? $category->getSubject() :
                                                        $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                                                    <span><?php echo $catName; ?></span>
                                                </td>
                                                <td class="visible-md visible-lg"><?php echo $item->getAuthor(); ?></td>
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
                                    <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
                                <?php endif; ?>
                            </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>