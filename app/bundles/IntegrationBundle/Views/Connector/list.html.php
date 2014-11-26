<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticIntegrationBundle:Connector:index.html.php');
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive panel-collapse pull out page-list">
        <table class="table table-hover table-striped table-bordered integration-list" id="connectorTable">
            <thead>
                <tr>
                    <th class="col-connector-name"><?php echo $view['translator']->trans('mautic.integration.connector.thead.name'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <a href="<?php echo $view['router']->generate('mautic_integration_connector_edit', array('name' => strtolower($item['name']))); ?>" data-toggle="ajaxmodal" data-target="#ConnectorEditModal">
                            <?php echo $item['name']; ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>

<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'ConnectorEditModal',
    'header' => false
));
