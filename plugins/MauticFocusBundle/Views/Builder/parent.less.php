<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
ob_start();
echo $view->render('MauticFocusBundle:Builder\Bar:parent.less.php');
echo $view->render('MauticFocusBundle:Builder\Modal:parent.less.php');
echo $view->render('MauticFocusBundle:Builder\Notification:parent.less.php');
echo $view->render('MauticFocusBundle:Builder\Page:parent.less.php');

$less = ob_get_clean();

require_once __DIR__.'/../../Include/lessc.inc.php';
$compiler = new \lessc();
$css      = $compiler->compile($less);

if (empty($preview) && $app->getEnvironment() != 'dev') {
    $css = \Minify_CSS::minify($css);
}

echo $css;
