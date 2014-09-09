<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Based on Sensio\DistributionBundle
 */

if (!isset($_SERVER['HTTP_HOST'])) {
    exit('This script cannot be run from the CLI. Run it from a browser.');
}

require_once __DIR__ . '/MauticRequirements.php';

$symfonyRequirements = new MauticRequirements();

$majorProblems = $symfonyRequirements->getFailedRequirements();
$minorProblems = $symfonyRequirements->getFailedRecommendations();

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <meta name="robots" content="noindex,nofollow" />
        <title>Mautic - Installation</title>
        <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico" />
        <link rel="apple-touch-icon" href="../assets/images/apple-touch-icon.png" />
        <link rel="stylesheet" href="assets/css/structure.css" media="all" />
        <link rel="stylesheet" href="assets/css/body.css" media="all" />
        <link rel="stylesheet" href="assets/css/install.css" media="all" />
    </head>
    <body>
        <div id="content">
            <div class="header clear-fix">
                <div class="header-logo"></div>

                <div class="search">
                  <form method="get" action="http://symfony.com/search">
                    <div class="form-row">

                      <label for="search-id">
                          <img src="assets/images/grey_magnifier.png" alt="Search on Symfony website" />
                      </label>

                      <input name="q" id="search-id" type="search" placeholder="Search on Symfony website" />

                      <button type="submit" class="sf-button">
                          <span class="border-l">
                            <span class="border-r">
                                <span class="btn-bg">OK</span>
                            </span>
                        </span>
                      </button>
                    </div>
                   </form>
                </div>
            </div>

            <div class="sf-reset">
                <div class="block">
                    <div class="symfony-block-content">
                        <h1 class="title">Welcome!</h1>
                        <p>Welcome to your new Mautic installation.</p>
                        <p>
                            This script will guide you through the basic configuration of your project.
                            You can also do the same by editing the ‘<strong>app/config/local.php</strong>’ file directly.
                        </p>

                        <?php if (count($majorProblems)): ?>
                            <h2 class="ko">Major problems</h2>
                            <p>Major problems have been detected and <strong>must</strong> be fixed before continuing:</p>
                            <ol>
                                <?php foreach ($majorProblems as $problem): ?>
                                    <li><?php echo $problem->getHelpHtml() ?></li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>

                        <?php if (count($minorProblems)): ?>
                            <h2>Recommendations</h2>
                            <p>
                                <?php if (count($majorProblems)): ?>Additionally, to<?php else: ?>To<?php endif; ?> enhance your Symfony experience,
                                it’s recommended that you fix the following:
                            </p>
                            <ol>
                                <?php foreach ($minorProblems as $problem): ?>
                                    <li><?php echo $problem->getHelpHtml() ?></li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>

                        <?php if ($symfonyRequirements->hasPhpIniConfigIssue()): ?>
                            <p id="phpini">*
                                <?php if ($symfonyRequirements->getPhpIniConfigPath()): ?>
                                    Changes to the <strong>php.ini</strong> file must be done in "<strong><?php echo $symfonyRequirements->getPhpIniConfigPath() ?></strong>".
                                <?php else: ?>
                                    To change settings, create a "<strong>php.ini</strong>".
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!count($majorProblems) && !count($minorProblems)): ?>
                            <p class="ok">Your configuration looks good to run Symfony.</p>
                        <?php endif; ?>

                        <ul class="symfony-install-continue">
                            <?php if (!count($majorProblems)): ?>
                                <li><a href="../index_dev.php/_configurator/">Configure your Symfony Application online</a></li>
                                <li><a href="../index.php/">Bypass configuration and go to the Welcome page</a></li>
                            <?php endif; ?>
                            <?php if (count($majorProblems) || count($minorProblems)): ?>
                                <li><a href="index.php">Re-check configuration</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="version">Mautic</div>
        </div>
    </body>
</html>
