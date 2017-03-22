<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticPluginBundle:Integration:index.html.php');
}
?>
<?php if (count($items)): ?>
<div class="pa-md bg-auto">
    <div class="row shuffle-integrations">
            <?php foreach ($items as $item): ?>
                <div class="shuffle shuffle-item grid ma-10 pull-left text-center integration plugin<?php echo $item['plugin']; ?> integration-<?php echo $item['name']; ?> <?php if (!$item['enabled']) {
    echo  'integration-disabled';
} ?>">
                    <div class="panel ovf-h pa-10">
                        <a href="<?php echo $view['router']->path(($item['isBundle'] ? 'mautic_plugin_info' : 'mautic_plugin_config'), ['name' => $item['name']]); ?>" data-toggle="ajaxmodal" data-target="#IntegrationEditModal" data-header="<?php echo $item['display']; ?>"<?php if ($item['isBundle']) {
    echo ' data-footer="false"';
} ?>>
                            <p><img style="height: 78px;" class="img img-responsive" src="<?php echo $view['assets']->getUrl($item['icon']); ?>" /></p>
                            <h5 class="mt-20">
                                <span class="ellipsis" data-toggle="tooltip" title="<?php echo $plugins[$item['plugin']]['name'].' - '.$item['display']; ?>"><?php echo $item['display']; ?>
                                </span>
                            </h5>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
    </div>
</div>
<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', [
    'id'            => 'IntegrationEditModal',
    'footerButtons' => true,
]); ?>

<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', [
        'message' => 'mautic.integrations.noresults',
        'tip'     => 'mautic.integration.noresults.tip',
    ]); ?>
<?php endif; ?>
