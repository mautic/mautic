<?php


require_once __DIR__.'/app/autoload.php';
// require_once __DIR__.'/app/bootstrap.php.cache';
require_once __DIR__.'/app/AppKernel.php';
require __DIR__.'/vendor/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MauticPatchTester
{
	public $localfile = 'patch-to-apply.patch';
	public $url = 'https://patch-diff.githubusercontent.com/raw/mautic/mautic/pull/';
	public $testerfilename;

	public function __construct()
	{

		$this->testerfilename =  basename(__FILE__);
		$allowedTasks = array(
			'apply',
			'remove'
		);



		$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';
		$patch = isset($_REQUEST['patch']) ? $_REQUEST['patch'] : '';
		$cmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : '';

		// Controller
		if (in_array($task, $allowedTasks)) {
			@set_time_limit(9999);
			if(empty($cmd)) {
				$cmd = [];
			}
			$count = 2 + count($cmd);
			$counter = 1;
			file_put_contents(__DIR__ . '/progress.json', json_encode(['progress'=>floor((100/$count)*$counter)]));
			$this->$task($patch);
			foreach ($cmd as $c => $value) {
				$counter++;
				file_put_contents(__DIR__ . '/progress.json', json_encode(['progress'=>floor((100/$count)*$counter)]));
				$this->executeCommand($c);
			}
			file_put_contents(__DIR__ . '/progress.json', json_encode(['progress'=>100]));
		}
		else
		{
			$this->start();
			file_put_contents(__DIR__ . '/progress.json', null);
		}
	}

	function apply($patch = null)
	{
		if ($patch) {
			$patchUrl = $this->url.$patch.'.patch';
			echo $patchUrl;
			$result = exec('curl ' . $patchUrl . ' | git apply');
			print_R($result);
		} else {
			echo 'Apply with no Patch ID';
		}
	}

	function remove($patch = null)
	{
		if ($patch) {
			$patchUrl = $this->url.$patch.'.patch';
			$result = exec('curl ' . $patchUrl . ' | git apply -R');

			return $result;
		} else {
			echo 'Could not remove Patch';
		}
	}

	function executeCommand($cmd){

		// cache clear by remove dir
		if($cmd == "cache:clear"){
			return exec('rm -r app/cache/prod/');
		}

		$fullCommand = explode(' ', $cmd);
		$command = $fullCommand[0];
		$argsCount = count($fullCommand) - 1;
		$args = array('console', $command);
		if ($argsCount) {
			for ($i = 1; $i <= $argsCount; $i++) {
				$args[] = $fullCommand[$i];
			}
		}
		defined('IN_MAUTIC_CONSOLE') or define('IN_MAUTIC_CONSOLE', 1);
		try {
			$input  = new ArgvInput($args);
			$output = new BufferedOutput();
			$kernel = new AppKernel('prod', false);
			$app    = new Application($kernel);
			$app->setAutoExit(false);
			$result = $app->run($input, $output);
			echo "<pre>\n".$output->fetch().'</pre>';
		} catch (\Exception $exception) {
			echo $exception->getMessage();
		}
	}


	// View
	function start()
	{ ?>

		<!DOCTYPE html>
		<html lang="en">
		<head>
			<title>Mautic Patch Tester</title>
				<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
			<link rel="icon" href="https://www.mautic.org/wp-content/uploads/2014/08/favicon.png">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">

		</head>
		<body>
		<div class="container">
			<nav class="navbar navbar-light bg-light navbar-expand-lg">
			  <a class="navbar-brand" href="https://www.mautic.org">
			    <img src="https://www.mautic.org/media/logos/notagline/horizontal/Mautic_Logo_RGB_LB.png" height="30" class="d-inline-block align-top" alt="">
			  </a>
			  <div class="collapse navbar-collapse" id="navbarNav">
				<ul class="nav navbar-nav">
					<li class="nav-item"><a class="nav-link" href="https://github.com/mautic/mautic/pulls?q=is%3Apr+is%3Aopen+label%3A%22Ready+To+Test%22" target="_blank">GitHub PR's</a></li>
				</ul>
			  </div>
			</nav>

			<?php
			if (!is_writable(dirname($this->localfile)))
			{
				$dir_class = "alert alert-danger";
				$msg       = "<p class='alert alert-danger'><span class='glyphicon glyphicon-warning-sign'></span> This path is not writable. Please change permissions before continuing.</p>";
				// $continue  = "disabled";
			}
			else
			{
				$dir_class = "alert alert-secondary";
				$msg       = "";
				$continue  = "";
			}
			?>

			<?php echo $msg; ?>

			<div class="container mt-4">
					<h1>Start Testing!</h1>
					<p class="lead">This app will allow you to immediately begin testing pull requests against your Mautic installation. You will need to make sure this file is in the root of your Mautic test instance.</p>

					<div class="h6">Current Path</div>
					<div class="<?php echo $dir_class; ?>"><?php echo __DIR__; ?></div>

					<div class="hidden progressMsg">
						Progress: <span class="label label-info">Ready.</span>
					</div>
					<div class="progress progress-striped mb-4">
						<div class="progress-bar progress-bar-info" role="progressbar" style="width: 0%">
						</div>
					</div>

			<div class="row">
				<div class="col">
					<form id="apply-form" action="" method="post">
						<input type="hidden" name="task" value="apply">
					<div class="card">
						<div class="card-body">
							<div class="card-title">
								<h4>Apply Pull Request</h4>
							</div>
							<div class="card-text">
								<div class="form-group">
									<label for="patch">Enter Pull Request Number</label>
									<input type='text' id='apply-patch' name='patch' class='form-control' />
								    <small class="form-text text-muted">e.g. Simply enter 3456 for PR #3456</small>
								</div>
								<div class="form-group">
									<h5>Run after apply pull request</h5>
									<div class="checkbox">
										<label>
											<input type="checkbox" class="cmd" id="cache-clear" name="cmd[cache:clear]" value="1">
											<label for="cache-clear">clear cache</label>
										</label>
									</div>
									<div class="checkbox">
										<label>
											<input class="cmd" type="checkbox" id="dsu" name="cmd[doctrine:schema:update --force]" value="1">
											<label for="dsu">doctrine:schema:update --force</label>
									</div>
									<div class="checkbox">
										<label>
											<input type="checkbox" id="mag" class="cmd" name="cmd[mautic:assets:generate]" value="1">
											<label for="mag">mautic:assets:generate</label>
											<small style="color:red">(it can take a few minutes)</small>
										</label>
									</div>
								</div>
								<span class="pt-3 text-small float-right text-success" id="pr-applied-message"></span>
								<input type="submit"  id="apply" class="btn btn-success btn-lg" data-loading-text="Applying..." value="Apply PR"/>
							</div>
						</div>
					</div>
					</form>
				</div>
				<div class="col">
					<form id="remove-form" action="" method="post">
					<input type="hidden" name="task" value="remove">

					<div class="card card-danger">
						<div class="card-body">
							<div class="card-title">
								<h4>Remove Pull Request</h4>
							</div>
							<div class="card-text">
								<div class="form-group">
									<label for="patch">Enter Pull Request Number</label>
									<input type='text' id='remove-patch' name='patch' class='form-control' />
								    <small class="form-text text-muted">e.g. Simply enter 3456 for PR #3456</small>
								</div>
								<div class="form-group">
									<h5>Run after remove pull request</h5>
									<div class="checkbox">
										<label>
											<input type="checkbox" class="cmd" id="cache-clear-r" name="cmd[cache:clear]" value="1">
											<label for="cache-clear-r">clear cache</label>
										</label>
									</div>
									<div class="checkbox">
										<label>
											<input class="cmd" type="checkbox" id="dsu-r" name="cmd[doctrine:schema:update --force]" value="1">
											<label for="dsu-r">doctrine:schema:update --force</label>
									</div>
									<div class="checkbox">
										<label>
											<input type="checkbox" id="mag-r" class="cmd" name="cmd[mautic:assets:generate]" value="1">
											<label for="mag-r">mautic:assets:generate</label>
											<small style="color:red">(it can take a few minutes)</small>
										</label>
									</div>
								<span class="pt-3 text-small float-right text-danger" id="pr-removed-message"></span>
									<input type="submit"  id="remove" class="btn btn-success btn-lg" data-loading-text="Removing..." value="Remove PR"/>
							</div>
						</div>
					</div>
						</form>
			</div>
			</div>
			</div>
			<div class="text-muted pt-4"><small>*This app does not yet take into account any pull requests that require database changes.</small></div>


		</div>
		<!-- /container -->

		<script src="//code.jquery.com/jquery.js"></script>
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>

		<script type="text/javascript">
			var finished = false;
			function progress() {
				jQuery.ajax({'url': 'progress.json', 'type': 'post', 'dataType': 'json'})
					.done(function (msg) {
						jQuery('.progress-bar').css('width', msg.progress + '%');
						jQuery('.label-info').html(msg.progress + '%');
					});
				if (!( finished )) {
					setTimeout(function () {
						progress();
					}, 1000);
				}
			}
			jQuery(document).ready(function () {
				jQuery('#apply-form').on( "submit", function( e ) {
					e.preventDefault();
					var patch = jQuery('#apply-patch').val();
					if(!patch) {  alert('Please enter a valid PR'); return false; }
					setTimeout(function () {
						progress();
					}, 1000);
					jQuery.ajax({'url': './<?php echo $this->testerfilename ?>', 'data': $( this ).serializeArray(), 'type': 'post', 'dataType': 'text'})
						.done(function () {
							jQuery('.progress-bar').css('width', '100%');
							jQuery('.label-info').html('100%');
							finished = true;
							jQuery('#pr-applied-message').html('PR #'+patch+' successfully applied');
							jQuery('#apply-patch').val('');
						});
					return false;
				});
				jQuery('#remove-form').on( "submit", function( e ) {
					e.preventDefault();
					var patch = jQuery('#remove-patch').val();
					if(!patch) { alert('Please enter a valid PR'); return; }
					setTimeout(function () {
						progress();
					}, 1000);
					jQuery.ajax({'url': './<?php echo $this->testerfilename ?>', 'data': $( this ).serializeArray(), 'type': 'post', 'dataType': 'text'})
						.done(function () {
							jQuery('.progress-bar').css('width', '100%');
							jQuery('.label-info').html('100%');
							finished = true;
							jQuery('#pr-removed-message').html('PR #'+patch+' successfully removed');
							jQuery('#remove-patch').val('');
						});
				});


			})
		</script>
		</body>
		</html>
	<?php }
}

new MauticPatchTester;
