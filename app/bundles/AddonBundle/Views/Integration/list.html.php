<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticAddonBundle:Integration:index.html.php');
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive panel-collapse pull out page-list">
        <table class="table table-hover table-striped table-bordered integration-list" id="integrationTable">
            <thead>
                <tr>
                    <th class="col-integration-name"><?php echo $view['translator']->trans('mautic.addon.integration.thead.name'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <a href="<?php echo $view['router']->generate('mautic_addon_integration_edit', array('name' => strtolower($item['name']))); ?>" data-toggle="ajaxmodal" data-target="#IntegrationEditModal">
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
    'id'     => 'IntegrationEditModal',
    'header' => false
));
