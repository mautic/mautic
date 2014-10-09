<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//extend the template chosen
$view->extend(":$template:page.html.php");


if ($googleAnalytics) {
    $gCode = <<<GA
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', '$googleAnalytics', 'auto');
ga('send', 'pageview');
GA;
    $view['assets']->addScriptDeclaration($gCode);
}

//Set the slots
foreach ($slots as $slot) {
    $value = isset($content[$slot]) ? $content[$slot] : "";
    $view['slots']->set($slot, $value);
}