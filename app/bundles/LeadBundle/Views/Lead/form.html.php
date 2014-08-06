<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$header = ($lead->getId()) ?
    $view['translator']->trans('mautic.lead.lead.header.edit',
        array('%name%' => $view['translator']->trans($lead->getPrimaryIdentifier()))) :
    $view['translator']->trans('mautic.lead.lead.header.new');

$view['slots']->set('mauticContent', 'lead');
$view['slots']->set("headerTitle", $header);

$groups = array_keys($fields);
?>

<?php echo $view['form']->start($form); ?>
<div class="scrollable">
	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">
						<?php echo $view['translator']->trans('mautic.lead.field.group.core'); ?>
					</h3>
				</div>
				<div class="panel-body">
					<?php foreach ($fields['core'] as $field): ?>
						<?php echo $view['form']->row($form[$field['alias']]); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="panel panel-teal">
				<div class="panel-heading">
					<h3 class="panel-title">
						<?php echo $view['translator']->trans('mautic.lead.field.group.extra'); ?>
					</h3>
				</div>
				<div class="panel-body">
					<?php echo $view['form']->row($form['owner_lookup']); ?>
				</div>
			</div>

			<?php if (isset($fields['social'])): ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">
							<?php echo $view['translator']->trans('mautic.lead.field.group.social'); ?>
						</h3>
					</div>
					<div class="panel-body">
						<?php foreach ($fields['social'] as $field): ?>
							<?php echo $view['form']->row($form[$field['alias']]); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if (isset($fields['professional'])): ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">
							<?php echo $view['translator']->trans('mautic.lead.field.group.professional'); ?>
						</h3>
					</div>
					<div class="panel-body">
						<?php foreach ($fields['professional'] as $field): ?>
							<?php echo $view['form']->row($form[$field['alias']]); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="row">
		<?php foreach ($groups as $k => $group): ?>
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">
							<?php echo $view['translator']->trans('mautic.lead.field.group.'.$group); ?>
						</h3>
					</div>
					<div class="panel-body">
						<?php foreach ($fields[$group] as $field): ?>
							<?php echo $view['form']->row($form[$field['alias']]); ?>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php echo $view['form']->end($form); ?>