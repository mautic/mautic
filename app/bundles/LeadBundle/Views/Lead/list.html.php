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

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.lead.lead.header.index'); ?></h3>
    </div>
    <div class="panel-toolbar-wrapper">
        <div class="panel-toolbar">
            <div class="checkbox custom-checkbox pull-left">
                <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#leadTable">
                <label for="customcheckbox-one0"><?php echo $view['translator']->trans('mautic.core.table.selectall'); ?></label>
            </div>
        </div>
        <div class="panel-toolbar text-right">
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-default"><i class="fa fa-upload"></i></button>
                <button type="button" class="btn btn-sm btn-default"><i class="fa fa-archive"></i></button>
            </div>

            <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></button>
        </div>
    </div>
    <div class="table-responsive panel-collapse pull out">
        <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered role-list" id="leadTable">
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
                        'orderBy'    => 'l.score',
                        'text'       => 'mautic.lead.lead.thead.score',
                        'class'      => 'col-lead-score'
                    ));
                    ?>

                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php $fields = $model->organizeFieldsByGroup($item->getFields()); ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                            'item'      => $item,
                            'edit'      => $security->hasEntityAccess(
                                $permissions['lead:leads:editown'],
                                $permissions['lead:leads:editother'],
                                $item->getOwner()
                            ),
                            'delete'    => $security->hasEntityAccess(
                                $permissions['lead:leads:deleteown'],
                                $permissions['lead:leads:deleteother'],
                                $item->getOwner()),
                            'routeBase' => 'lead',
                            'menuLink'  => 'mautic_lead_index',
                            'langVar'   => 'lead.lead'
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
                        <?php echo $item->getScore(); ?>
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
    <div class="footer-margin"></div>
</div>