<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Default:content.html.php');
}
$view['slots']->set('mauticContent', 'config');
?>
<?php if (!empty($params)) : ?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- step container -->
    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">

            <!-- Nav tabs -->
            <ul class="list-group list-group-tabs" role="tablist">
            <?php $i = 0; ?>
            <?php foreach ($params as $key => $paramArray) : ?>
                <li role="presentation" class="list-group-item <?php echo $i == 0 ? 'in active' : ''; ?>">
                    <a href="#<?php echo $key; ?>" aria-controls="<?php echo $key; ?>" role="tab" data-toggle="tab" class="steps">
                        <?php echo $paramArray['bundle']; ?>
                    </a>
                </li>
                <?php $i++; ?>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-l">
        <!-- Tab panes -->
        <?php echo $view['form']->start($form); ?>
        <div class="tab-content">
            <?php $i = 0; ?>
            <?php foreach ($params as $key => $paramArray) : ?>
            <div role="tabpanel" class="tab-pane fade <?php echo $i == 0 ? 'in active' : ''; ?> bdr-w-0" id="<?php echo $key; ?>">
                <div class="pt-md pr-md pl-md pb-md">
                <?php if (isset($paramArray['parameters']) && isset($paramArray['formAlias'])) : ?>
                    <?php foreach ($paramArray['parameters'] as $paramKey => $paramValue) : ?>
                        <?php if (isset($form[$paramArray['formAlias']][$paramKey])) : ?>
                        <div class="col-md-6">
                            <?php echo $view['form']->row($form[$paramArray['formAlias']][$paramKey]); ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else : ?>
                <?php foreach ($paramArray as $paramKey => $paramValue) : ?>
                    <div class="col-md-6">
                        <?php echo $view['form']->row($form[$paramKey]); ?>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>
            <?php $i++; ?>
            <?php endforeach; ?>
        </div>
        <?php echo $view['form']->end($form); ?>
    </div>
</div>

<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
