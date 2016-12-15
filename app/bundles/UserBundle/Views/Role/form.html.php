<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'role');

$view['assets']->addScriptDeclaration('MauticVars.permissionList = '.json_encode($permissionsConfig['list']), 'bodyClose');

$objectId = $form->vars['data']->getId();
if (!empty($objectId)) {
    $name   = $form->vars['data']->getName();
    $header = $view['translator']->trans('mautic.user.role.header.edit', ['%name%' => $name]);
} else {
    $header = $view['translator']->trans('mautic.user.role.header.new');
}
$view['slots']->set('headerTitle', $header);
?>

<?php echo $view['form']->start($form); ?>
<div class="box-layout">
	<div class="col-xs-12 bg-white height-auto">
		<!-- tabs controls -->
		<ul class="bg-auto nav nav-tabs pr-md pl-md">
			<li class="active"><a href="#details-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.details'); ?></a></li>
			<li class=""><a href="#permissions-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.user.role.permissions'); ?></a></li>
		</ul>
		<!--/ tabs controls -->

		<div class="tab-content pa-md">
			<div class="tab-pane fade in active bdr-w-0 height-auto" id="details-container">
				<div class="row">
					<div class="pa-md">
						<div class="col-md-6">
							<?php echo $view['form']->row($form['name']); ?>
						</div>
						<div class="col-md-6">
							<?php echo $view['form']->row($form['isAdmin']); ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="pa-md">
							<?php echo $view['form']->row($form['description']); ?>
						</div>
					</div>
				</div>
			</div>

			<?php $hidePerms = $form['isAdmin']->vars['data']; ?>
			<div class="tab-pane fade bdr-w-0" id="permissions-container">
				<div id="rolePermissions"<?php if ($hidePerms) {
    echo ' class="hide"';
} ?>>
					<!-- start: box layout -->
					<div class="box-layout">
						<!-- step container -->
						<div class="col-md-5 bg-white height-auto">
							<div class="pr-lg pl-lg pt-md pb-md">

								<!-- Nav tabs -->
								<ul class="list-group list-group-tabs" role="tablist">
									<?php $i = 0; ?>
									<?php foreach ($permissionsConfig['config'] as $bundle => $config) : ?>
										<li role="presentation" class="list-group-item <?php echo $i === 0 ? 'in active' : ''; ?>">
											<a href="#<?php echo $bundle; ?>PermissionTab" aria-controls="<?php echo $bundle; ?>PermissionTab" role="tab" data-toggle="tab" class="steps">
												<span><?php echo $config['label']; ?></span>
												<span class="permission-ratio"> (<span class="<?php echo $bundle; ?>_granted"><?php echo $config['ratio'][0]; ?></span> / <span class="<?php echo $bundle; ?>_total"><?php echo $config['ratio'][1]; ?></span>)</span>
											</a>
										</li>
										<?php ++$i; ?>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>

						<!-- container -->
						<div class="col-md-7 bg-auto height-auto bdr-l">
							<div class="tab-content">
								<?php
                                $permissions = $form['permissions']->children;
                                $i           = 0;
                                foreach ($permissions as $child):
                                    if ($child->vars['value'] == 'newbundle'):
                                        if ($i > 0): // Close tab panel
                                            echo "</div>\n</div>\n";
                                        endif;
                                        echo '<div role="tabpanel" class="tab-pane fade'.($i === 0 ? ' in active' : '').' bdr-w-0" id="'.$child->vars['name'].'PermissionTab">'."\n";
                                        echo '<div class="pt-md pr-md pl-md pb-md">'."\n";
                                        $child->setRendered();
                                    else:
                                        echo $view['form']->row($child);
                                    endif;
                                    ++$i;
                                endforeach;
                                //close last tab
                                echo "</div>\n</div>\n";
                                $form['permissions']->setRendered();
                                ?>
							</div>
						</div>
					</div>
				</div>
				<div id="isAdminMessage"<?php if (!$hidePerms) {
                                    echo ' class="hide"';
                                } ?>>
					<div class="alert alert-warning">
						<h4><?php echo $view['translator']->trans('mautic.user.role.permission.isadmin.header'); ?></h4>
						<p><?php echo $view['translator']->trans('mautic.user.role.permission.isadmin.message'); ?></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php echo $view['form']->end($form); ?>
