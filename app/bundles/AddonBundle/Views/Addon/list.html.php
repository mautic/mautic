<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticAddonBundle:Addon:index.html.php');
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive panel-collapse pull out page-list">
        <table class="table table-hover table-striped table-bordered addon-list" id="addonTable">
            <thead>
                <tr>
                    <!--
                    <th class="col-addon-actions pl-20">
                        <div class="checkbox-inline custom-primary">
                            <label class="mb-0 pl-10">
                                <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#addonTable">
                                <span></span>
                            </label>
                        </div>
                    </th>
                    -->
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'addon',
                        'orderBy'    => 'i.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-addon-name',
                        'default'    => true
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'addon',
                        'orderBy'    => 'i.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'col-addon-id'
                    ));
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <!--
                    <td>
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', array(
                            'item'       => $item,
                            'routeBase'  => 'addon'
                        ));
                        ?>
                    </td>
                    -->
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php',array(
                            'item'       => $item,
                            'model'      => 'addon',
                            'backdrop'   => true
                        )); ?>
                        <?php if ($integrationHelper->getIntegrationCount($item->getBundle())): ?>
                        <a href="<?php echo $view['router']->generate('mautic_addon_integration_index', array("addon" => $item->getId())); ?>" data-toggle="ajax">
                            <?php echo $item->getName(); ?>
                        </a>
                        <?php else: ?>
                        <?php echo $item->getName(); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => count($items),
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_addon_index',
            "baseUrl"         => $view['router']->generate('mautic_addon_index'),
            'sessionVar'      => 'page'
        )); ?>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
