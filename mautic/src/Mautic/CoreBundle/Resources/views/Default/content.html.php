<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$app->getRequest()->isXmlHttpRequest()):
    //load base template
    $view->extend('MauticCoreBundle:Default:base.html.php');
endif;
?>
<?php if ($view["slots"]->has("headerTitle")): ?>
<div class="main-panel-header">
    <h1><?php $view["slots"]->output("headerTitle"); ?></h1>
</div>
<?php endif; ?>

<?php $view['slots']->output('_content'); ?>