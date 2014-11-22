<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticIntegrationBundle:Integration:index.html.php');
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive panel-collapse pull out page-list">
        <table class="table table-hover table-striped table-bordered integration-list" id="integrationTable">
            <thead>
                <tr>
                    <th class="col-integration-actions pl-20">
                        <div class="checkbox-inline custom-primary">
                            <label class="mb-0 pl-10">
                                <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#integrationTable">
                                <span></span>
                            </label>
                        </div>
                    </th>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'integration',
                        'orderBy'    => 'i.name',
                        'text'       => 'mautic.integration.thead.name',
                        'class'      => 'col-integration-name',
                        'default'    => true
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'integration',
                        'orderBy'    => 'i.id',
                        'text'       => 'mautic.integration.thead.id',
                        'class'      => 'col-integration-id'
                    ));
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                            'item'       => $item,
                            'routeBase'  => 'integration',
                            'menuLink'   => 'mautic_integration_index',
                            'langVar'    => 'integration',
                            'nameGetter' => 'getName'
                        ));
                        ?>
                    </td>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $item,
                            'model'      => 'integration'
                        )); ?>
                        <a href="<?php echo $view['router']->generate('mautic_integration_action',
                            array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                           data-toggle="ajax">
                            <?php echo $item->getName(); ?>
                        </a>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => count($items),
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_integration_index',
            "baseUrl"         => $view['router']->generate('mautic_integration_index'),
            'sessionVar'      => 'page'
        )); ?>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
