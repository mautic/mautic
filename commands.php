<?php

if (!isset($_GET['mautic-newcom4u-com'])) {
    echo 'The secret phrase is wrong.';
    die;
}

$link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$allowedTasks = array(
    'cache:clear',
    'cache:clear --no-warmup',
    //'mautic:install:data',
    'mautic:campaigns:trigger',
    'mautic:campaigns:rebuild',
    //'mautic:social:monitoring',
    //'mautic:iplookup:download',
    //'mautic:emails:send',
    //'mautic:email:fetch',
    //'mautic:messages:send',
    'mautic:integration:fetchleads --integration=Sugarcrm',
        /*
    'mautic:integration:fetchleads --integration=Hubspot',*/
    'mautic:segments:update',
    'doctrine:migration:status',
/*    'doctrine:migrations:migrate',*/
    'doctrine:schema:update --dump-sql',
/*    'doctrine:schema:update --force',
    'mautic:maintenance:cleanup --days-old=365 --dry-run'*/
);

if (!isset($_GET['task'])) {
    echo '<html><head></head><body style="font-family: Open Sans, Helvetica, Arial, sans-serif;">';
    echo '<p>Specify what task to run. You can run these:';
    echo '<ul>';
    foreach ($allowedTasks as $task) {
        $href = $link . '&task=' . urlencode($task);
        echo '<li><a href="' . $href . '">' . $task . '</a></li>';
    }
    echo '</ul><br/><a href="https://www.mautic.org/docs/setup/index.html">Read more</a>';
    echo '<br/><b style="color:red">Please, backup your database before executing the doctrine commands!</b></p>';
    die;
}

$task = urldecode($_GET['task']);
if (!in_array($task, $allowedTasks)) {
    echo 'Task ' . $task . ' is not allowed.';
    die;
}
$fullCommand = explode(' ', $task);
$command = $fullCommand[0];
$argsCount = count($fullCommand) - 1;
$args = array('console', $command);
if ($argsCount) {
    for ($i = 1; $i <= $argsCount; $i++) {
        $args[] = $fullCommand[$i];
    }
}
echo '<html><head></head><body style="font-family: Open Sans, Helvetica, Arial, sans-serif;">';
echo '<h3>Executing ' . implode(' ', $args) . '</h3>';

require_once __DIR__.'/app/autoload.php';
// require_once __DIR__.'/app/bootstrap.php.cache';
require_once __DIR__.'/app/AppKernel.php';
require __DIR__.'/vendor/autoload.php';
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

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