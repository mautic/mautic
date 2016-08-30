<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticCompanyBundle:Company:index.html.php');
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
                        'orderBy'    => 's.name',
                        'text'       => 'mautic.company.name',
                        'class'      => 'col-company-name',
                        'default'    => true
                    )
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'company',
                        'text'       => 'mautic.company.phone',
                        'class'      => 'visible-md visible-lg col-company-category'
                    )
                );
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'company',
                        'text'       => 'mautic.company.email',
                        'class'      => 'visible-md visible-lg col-company-category'
                    )
                );
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'company',
                        'text'       => 'mautic.company.website',
                        'class'      => 'visible-md visible-lg col-company-category'
                    )
                );
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'segment',
                    'text'       => 'mautic.lead.list.thead.leadcount',
                    'class'      => 'visible-md visible-lg col-leadlist-leadcount'
                ));
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'company',
                        'orderBy'    => 's.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-company-id'
                    )
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            array(
                                'item'            => $item,
                                'templateButtons' => array(
                                    'edit'   => $permissions['company:companies:edit'],
                                    'clone'  => $permissions['company:companies:create'],
                                    'delete' => $permissions['company:companies:delete'],
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
                                <?php echo $item->getName(); ?>
                            </a>
                        </div>
                        </td>
                    <td class="visible-md visible-lg">
                        <?php echo $item->getPhone(); ?>
                    </td>
                    <td>
                            <div class="text-muted mt-4">
                                <small><?php echo $item->getEmail(); ?></small>
                            </div>
                    </td>

                    <td class="visible-md visible-lg">
                        <?php echo $item->getWebsite(); ?>
                    </td>
                    <td class="visible-md visible-lg">
                        <a class="label label-primary" href="<?php echo $view['router']->path('mautic_contact_index', array('search' => $view['translator']->trans('mautic.company.lead.searchcommand.company') . ':' . $item->getName())); ?>" data-toggle="ajax"<?php echo ($leadCounts[$item->getId()] == 0) ? "disabled=disabled" : ""; ?>>
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
                "totalItems" => count($items),
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
