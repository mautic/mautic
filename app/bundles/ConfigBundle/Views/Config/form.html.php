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
?>
<?php if (!empty($params)) : ?>

<div class="panel panel-default page-list bdr-t-wdh-0">
    <div class="panel-body">
        <div role="tabpanel">

            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist">
            <?php $i = 0; ?>
            <?php foreach ($params as $key => $paramArray) : ?>
                <li role="presentation" class="<?php echo $i == 0 ? 'in active' : ''; ?>">
                    <a href="#<?php echo $key; ?>" aria-controls="<?php echo $key; ?>" role="tab" data-toggle="tab">
                        <?php echo $key; ?>
                    </a>
                </li>
                <?php $i++; ?>
            <?php endforeach; ?>
            </ul>

            <!-- Tab panes -->
            <?php echo $view['form']->start($form); ?>
            <div class="tab-content">
                <?php $i = 0; ?>
                <?php foreach ($params as $key => $paramArray) : ?>
                <div role="tabpanel" class="tab-pane fade <?php echo $i == 0 ? 'in active' : ''; ?> bdr-w-0" id="<?php echo $key; ?>">
                    <div class="pt-md pr-md pl-md pb-md">
                    <?php foreach ($paramArray as $paramKey => $paramValue) : ?>
                        <div class="col-md-6">
                            <?php echo $view['form']->row($form[$paramKey]); ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php $i++; ?>
                <?php endforeach; ?>
            </div>
            <?php echo $view['form']->end($form); ?>

        </div>
    </div>
</div>

<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
