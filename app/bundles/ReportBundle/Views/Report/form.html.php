<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'report');

$header = ($report->getId()) ?
    $view['translator']->trans('mautic.report.report.header.edit',
        array('%name%' => $view['translator']->trans($report->getTitle()))) :
    $view['translator']->trans('mautic.report.report.header.new');

$view['slots']->set("headerTitle", $header);
?>
<?php echo $view['form']->start($form); ?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-r">
		<div class="pa-md">
			<?php echo $view['form']->row($form['title']); ?>
			<?php echo $view['form']->row($form['source']); ?>
			<?php echo $view['form']->row($form['columns']); ?>
			<?php echo $view['form']->row($form['filters']); ?>
		</div>
    </div>
    <div class="col-md-3 bg-white height-auto">
		<div class="pr-lg pl-lg pt-md pb-md">
			<?php echo $view['form']->rest($form); ?>
		</div>
	</div>
</div>
<?php echo $view['form']->end($form); ?>
