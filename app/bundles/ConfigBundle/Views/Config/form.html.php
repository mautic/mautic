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
<?php echo $view['form']->start($form); ?>
<div class="panel panel-default page-list bdr-t-wdh-0">
    <div class="panel-body">
        <div class="row">
            <?php foreach ($params as $key => $paramArray) : ?>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                    	<h3 class="panel-title"><?php echo $key; ?></h3>
                    </div>
                    <div class="panel-body">
                        <?php foreach ($paramArray as $paramKey => $paramValue) : ?>
                        <?php echo $view['form']->row($form[$paramKey]); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
