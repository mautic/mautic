<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticLeadBundle:Company:index.html.php');
}
?>

<?php if (count($items)): ?>
    <div class="table-responsive page-list">
        <table class="table table-hover table-striped table-bordered company-list" id="companyTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'checkall' => 'true',
                        'target'   => '#companyTable'
                    )
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'company',
                        'text'       => 'mautic.company.name',
                        'class'      => 'col-company-name',
                        'orderBy'    => 'comp.companyname',
                    )
                );
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'company',
                        'text'       => 'mautic.company.email',
                        'class'      => 'visible-md visible-lg col-company-category',
                        'orderBy'    => 'comp.companyemail',
                    )
                );
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'company',
                        'text'       => 'mautic.company.website',
                        'class'      => 'visible-md visible-lg col-company-website',
                        'orderBy'    => 'comp.companywebsite',
                    )
                );
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'company',
                    'text'       => 'mautic.lead.list.thead.leadcount',
                    'class'      => 'visible-md visible-lg col-leadlist-leadcount'
                ));
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'company',
                        'orderBy'    => 'comp.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-company-id'
                    )
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php $fields = $item->getFields(); ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            array(
                                'item'            => $item,
                                'templateButtons' => array(
                                    'edit'   => $permissions['lead:leads:editother'],
                                    'clone'  => $permissions['lead:leads:create'],
                                    'delete' => $permissions['lead:leads:deleteother'],
                                ),
                                'routeBase'       => 'company'
                            )
                        );
                        ?>
                    </td>
                    <td>
                        <div>

                            <a href="<?php echo $view['router']->generate(
                                'mautic_company_action',
                                array("objectAction" => "edit", "objectId" => $item->getId())
                            ); ?>" data-toggle="ajax">
                                <?php if (isset($fields['core']['companyname'])) :?>
                                <?php echo $fields['core']['companyname']['value']; ?>
                                <?php   endif; ?>
                            </a>
                        </div>
                        </td>
                    <td>
                            <div class="text-muted mt-4">
                <?php if (isset($fields['core']['companyemail'])) :?>
                    <small>
                        <?php echo $fields['core']['companyemail']['value'] ;?>
                    </small>
                <?php   endif; ?>
                            </div>
                    </td>

                    <td class="visible-md visible-lg">
                        <?php if (isset($fields['core']['companywebsite'])) :?>
                        <?php echo $fields['core']['companywebsite']['value']; ?>
                        <?php   endif; ?>
                    </td>
                    <td class="visible-md visible-lg">
                        <a class="label label-primary" href="<?php echo $view['router']->path('mautic_contact_index', array('search' => $view['translator']->trans('mautic.company.lead.searchcommand.company') . ':' . $fields['core']['companyname']['value'])); ?>" data-toggle="ajax"<?php echo ($leadCounts[$item->getId()] == 0) ? "disabled=disabled" : ""; ?>>
                            <?php echo $view['translator']->transChoice('mautic.lead.company.viewleads_count', $leadCounts[$item->getId()], array('%count%' => $leadCounts[$item->getId()])); ?>
                        </a>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <?php echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            array(
                "totalItems" => $totalItems,
                "page"       => $page,
                "limit"      => $limit,
                "menuLinkId" => 'mautic_company_index',
                "baseUrl"    => $view['router']->generate('mautic_company_index'),
                'sessionVar' => 'company'
            )
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render(
        'MauticCoreBundle:Helper:noresults.html.php',
        array('tip' => 'mautic.company.action.noresults.tip')
    ); ?>
<?php endif; ?>
