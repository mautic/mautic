<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticAddonBundle:Integration:index.html.php');
?>
<?php if (count($items)): ?>
<div class="pa-md bg-auto">
    <div class="row shuffle-integrations">
            <?php foreach ($items as $item): ?>
                <div class="shuffle shuffle-item grid ma-10 pull-left text-center integration addon<?php echo $item['addon']; ?> integration-<?php echo $item['name']; ?>">
                    <div class="panel ovf-h pa-10">
                        <a href="<?php echo $view['router']->generate('mautic_addon_integration_edit', array('name' => $item['name'])); ?>" data-toggle="ajaxmodal" data-target="#IntegrationEditModal" data-header="<?php echo $item['display']; ?>">
                            <p><img class="img img-responsive" src="<?php echo $view['assets']->getUrl($item['icon']); ?>" /></p>
                            <h5 class="mt-20">
                                <span class="ellipsis" data-toggle="tooltip" title="<?php echo $addons[$item['addon']]['name'] . ' - ' . $item['display']; ?>"><?php echo $item['display']; ?>
                                    <?php $class = (!$item['enabled']) ? ' hide' : '';?>
                                    <i class="fa fa-2x fa-check text-success<?php echo $class; ?>" style="position: absolute; top: -10px; right: -5px;"></i>
                                </span>
                            </h5>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
    </div>
</div>
<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'            => 'IntegrationEditModal',
    'footerButtons' => true
)); ?>

<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', array(
        'message' => 'mautic.integrations.noresults',
        'tip'     => 'mautic.integration.noresults.tip'
    )); ?>
<?php endif; ?>
