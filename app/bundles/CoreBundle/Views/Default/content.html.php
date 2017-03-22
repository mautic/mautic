<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$request     = $app->getRequest();
$contentOnly = $request->get('contentOnly', false) || $view['slots']->get('contentOnly', false) || !empty($contentOnly);
$modalView   = $request->get('modal', false) || $view['slots']->get('inModal', false) || !empty($modalView);

if (!$request->isXmlHttpRequest() && !$modalView):
    //load base template
    $template = ($contentOnly) ? 'slim' : 'base';
    $view->extend("MauticCoreBundle:Default:$template.html.php");
endif;
?>

<?php if (!$modalView): ?>
<div class="content-body">
    <?php echo $view->render('MauticCoreBundle:Default:pageheader.html.php'); ?>
	<?php $view['slots']->output('_content'); ?>
</div>

<?php $view['slots']->output('modal'); ?>
<?php echo $view['security']->getAuthenticationContent(); ?>
<?php else: ?>
<?php $view['slots']->output('_content'); ?>
<?php endif; ?>
