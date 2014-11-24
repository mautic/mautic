<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticInstallBundle:Install:content.html.php');
}

$header = $view['translator']->trans('mautic.install.install.heading.user.configuration');
$view['slots']->set("headerTitle", $header);
?>

<h2 class="page-header">
	<?php echo $header; ?>
</h2>
<p><?php echo $view['translator']->trans('mautic.install.install.user.introtext'); ?></p>
<?php echo $view['form']->form($form); ?>
