<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticLeadBundle:Lead:index.html.php');
?>

<div class="panel panel-default page-list">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.lead.lead.header.index'); ?></h3>
        <div class="panel-toolbar text-right">
            <?php echo $view->render('MauticCoreBundle:Default:search.html.php'); ?>
        </div>
    </div>
    <div class="panel-body">
        <div class="box-layout">
            <div class="col-xs-6 va-m">
                <div class="checkbox-inline custom-primary">
                    <label class="mb-0">
                        <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#leadTable">
                        <span></span>
                        <?php echo $view['translator']->trans('mautic.core.table.selectall'); ?>
                    </label>
                </div>
            </div>
            <div class="col-xs-6 va-m text-right">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-default"><i class="fa fa-upload"></i></button>
                    <button type="button" class="btn btn-sm btn-default"><i class="fa fa-archive"></i></button>
                </div>
                <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></button>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered" id="leadTable">
            <thead>
                <tr>
                    <th class="col-lead-actions"></th>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'lead',
                        'orderBy'    => 'l.lastname, l.firstname, l.company, l.email',
                        'text'       => 'mautic.lead.lead.thead.name',
                        'class'      => 'col-lead-name',
                        'default'    => true
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'lead',
                        'orderBy'    => 'l.email',
                        'text'       => 'mautic.lead.lead.thead.email',
                        'class'      => 'col-lead-email'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'lead',
                        'orderBy'    => 'l.city, l.state',
                        'text'       => 'mautic.lead.lead.thead.location',
                        'class'      => 'col-lead-location'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'lead',
                        'orderBy'    => 'l.points',
                        'text'       => 'mautic.lead.lead.thead.points',
                        'class'      => 'col-lead-points'
                    ));
                    ?>

                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php $fields = $item->getFields(); ?>
                <tr>
                    <td>
                        <?php
                        $hasEditAccess = $security->hasEntityAccess(
                            $permissions['lead:leads:editown'],
                            $permissions['lead:leads:editother'],
                            $item->getOwner()
                        );

                        $custom = array();
                        if ($hasEditAccess && !empty($currentList)) {
                            //this lead was manually added to a list so give an option to remove them
                            $custom[] = array(
                                'attr' => array(
                                    'href' => $view['router']->generate('mautic_leadlist_action', array(
                                        "objectAction" => "removelead",
                                        "objectId" => $currentList['id'],
                                        "leadId"   => $item->getId()
                                    )),
                                    'data-toggle' => "ajax",
                                    'data-method' => 'POST'
                                ),
                                'label' => 'mautic.lead.lead.remove.fromlist',
                                'icon'  => 'fa-remove'
                            );
                        }

                        echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                            'item'      => $item,
                            'edit'      => $hasEditAccess,
                            'delete'    => $security->hasEntityAccess(
                                $permissions['lead:leads:deleteown'],
                                $permissions['lead:leads:deleteother'],
                                $item->getOwner()),
                            'routeBase' => 'lead',
                            'menuLink'  => 'mautic_lead_index',
                            'langVar'   => 'lead.lead',
                            'custom'    => $custom
                        ));
                        ?>
                    </td>
                    <td>
                        <a href="<?php echo $view['router']->generate('mautic_lead_action',
                            array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                           data-toggle="ajax">
                            <div><?php echo $item->getPrimaryIdentifier(); ?></div>
                            <div class="small"><?php echo $item->getSecondaryIdentifier(); ?></div>
                        </a>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $fields['core']['email']['value']; ?></td>
                    <td class="visible-md visible-lg"><?php
                        if (!empty($fields['core']['city']) && !empty($fields['core']['state']))
                            echo $fields['core']['city']['value'] . ', ' . $fields['core']['state']['value'];
                        elseif (!empty($fields['core']['city']))
                            echo $fields['core']['city']['value'];
                        elseif (!empty($fields['core']['state']))
                            echo $fields['core']['state']['value'];
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        $color = $item->getColor();
                        $style = !empty($color) ? ' style="background-color: ' . $color . ' !important;"' : '';
                        ?>
                        <span class="badge"<?php echo $style; ?>><?php echo $item->getPoints(); ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
        <?php endif; ?>
    </div>
    <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => $totalItems,
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_lead_index',
            "baseUrl"         => $view['router']->generate('mautic_lead_index'),
            "tmpl"            => $indexMode,
            'sessionVar'      => 'lead'
        )); ?>
    </div>
</div>
