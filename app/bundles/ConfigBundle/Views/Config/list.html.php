<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Default:content.html.php');
}
?>
<?php if (!empty($params)) : ?>
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
                        <pre><?php print_r($paramArray); ?></pre>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Default:noresults.html.php'); ?>
<?php endif; ?>
