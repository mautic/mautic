<?php
$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set("headerTitle",
	$view['translator']->trans('mautic.ines.page.title')
);

?>

<div class="panel panel-default mnb-5 bdr-t-wdh-0">
	<div class="page-list">
		<div id="ines-sync-logs">

			<div class="table-responsive">
			    <table class="table table-hover table-striped table-bordered" id="inesTable">
			        <thead>
			            <tr>
							<th>
								<span><?php echo $view['translator']->trans('mautic.ines.page.columns.dateadd') ?></span>
								<em><?php echo $view['translator']->trans('mautic.ines.page.columns.dateadd.subtitle') ?></em>
							</th>
							<th>
								<span><?php echo $view['translator']->trans('mautic.ines.page.columns.action') ?></span>
								<em><?php echo $view['translator']->trans('mautic.ines.page.columns.action.subtitle') ?></em>
							</th>
							<th>
								<span><?php echo $view['translator']->trans('mautic.ines.page.columns.email') ?></span>
								<em><?php echo $view['translator']->trans('mautic.ines.page.columns.email.subtitle') ?></em>
							</th>
							<th>
								<span><?php echo $view['translator']->trans('mautic.ines.page.columns.company') ?></span>
								<em><?php echo $view['translator']->trans('mautic.ines.page.columns.company.subtitle') ?></em>
							</th>
							<th>
								<span><?php echo $view['translator']->trans('mautic.ines.page.columns.status') ?></span>
								<em><?php echo $view['translator']->trans('mautic.ines.page.columns.status.subtitle') ?></em>
							</th>
							<th>
								<span><?php echo $view['translator']->trans('mautic.ines.page.columns.counter') ?></span>
								<em><?php echo $view['translator']->trans('mautic.ines.page.columns.counter.subtitle') ?></em>
							</th>
							<th>
								<span><?php echo $view['translator']->trans('mautic.ines.page.columns.dateupdate') ?></span>
								<em><?php echo $view['translator']->trans('mautic.ines.page.columns.dateupdate.subtitle') ?></em>
							</th>
			            </tr>
			        </thead>
			        <tbody>
						<?php foreach($items as $item) { ?>
							<tr>
								<td>
				                	<?php echo $item->getDateAdded()->format('d-m-Y à H:i') ?>
				                </td>
								<td>
				                	<?php echo $item->getAction() ?>
				                </td>
				                <td>
				                	<?php echo $item->getLeadEmail() ?>
				                </td>
				                <td>
				                	<?php echo $item->getLeadCompany() ?>
				                </td>
								<td>
				                	<?php echo $item->getStatus() ?>
				                </td>
								<td>
				                	<?php echo $item->getCounter() ?>
				                </td>
								<td>
				                	<?php echo $item->getDateLastUpdate()->format('d-m-Y à H:i') ?>
				                </td>
				        	</tr>
						<?php } ?>
			        </tbody>
			    </table>
			</div>

		</div>
	</div>
</div>


<style>

	#ines-sync-logs table tr th {
		vertical-align:top;
	}

	#ines-sync-logs table tr th em {
		display:block;
		font-weight:normal;
		font-size:12px;
		padding-top:5px;
	}
</style>
