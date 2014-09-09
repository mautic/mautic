<?php

require_once dirname(__FILE__) . '/MauticRequirements.php';

$symfonyRequirements = new MauticRequirements();

$iniPath = $symfonyRequirements->getPhpIniConfigPath();

echo "********************************\n";
echo "*                              *\n";
echo "*  Symfony requirements check  *\n";
echo "*                              *\n";
echo "********************************\n\n";

echo $iniPath ? sprintf("* Configuration file used by PHP: %s\n\n", $iniPath) : "* WARNING: No configuration file (php.ini) used by PHP!\n\n";

echo "** ATTENTION **\n";
echo "*  The PHP CLI can use a different php.ini file\n";
echo "*  than the one used with your web server.\n";
if ('\\' == DIRECTORY_SEPARATOR) {
    echo "*  (especially on the Windows platform)\n";
}
echo "*  To be on the safe side, please also launch the requirements check\n";
echo "*  from your web server using the web/config.php script.\n";

echo_title('Mandatory requirements');

$checkPassed = true;
foreach ($symfonyRequirements->getRequirements() as $req) {
    /** @var $req Requirement */
    echo_requirement($req);
    if (!$req->isFulfilled()) {
        $checkPassed = false;
    }
}

echo_title('Optional recommendations');

foreach ($symfonyRequirements->getRecommendations() as $req) {
    echo_requirement($req);
}

exit($checkPassed ? 0 : 1);

/**
 * Prints a Requirement instance
 */
function echo_requirement(Requirement $requirement)
{
    $result = $requirement->isFulfilled() ? 'OK' : ($requirement->isOptional() ? 'WARNING' : 'ERROR');
    echo ' ' . str_pad($result, 9);
    echo $requirement->getTestMessage() . "\n";

    if (!$requirement->isFulfilled()) {
        echo sprintf("          %s\n\n", $requirement->getHelpText());
    }
}

function echo_title($title)
{
    echo "\n** $title **\n\n";
}
