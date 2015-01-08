<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
foreach ($slots as $slot => $slotConfig) {

	// backward compatibility - if slotConfig array does not exist
    if (is_numeric($slot)) {
        $slot = $slotConfig;
        $slotConfig = array();
    }

    // define default config if does not exist
    if (!isset($slotConfig['type'])) {
        $slotConfig['type'] = 'html';
    }

    if ($slotConfig['type'] == 'html' || $slotConfig['type'] == 'text') {
	    $value = isset($content[$slot]) ? $content[$slot] : "";
	    $view['slots']->set($slot, $value);
	}

	if ($slotConfig['type'] == 'slideshow') {
		if (isset($content[$slot])) {
			$options = json_decode($content[$slot], true);
		} else {
			$options = array(
	            'width' => '100%',
	            'height' => '250px',
	            'background-color' => 'transparent',
	            'show-arrows' => false,
	            'show-dots' => true,
	            'interval' => 5000,
	            'pause' => 'hover',
	            'wrap' => true,
	            'keyboard' => true,
	        );
		}

		// Create sample slides for first time or if all slides were deleted
        if (empty($options['slides'])) {
            $options['slides'] =  array (
                array (
                    'order' => 0,
                    'background-image' => 'http://placehold.it/1900x250/4e5d9d&text=Slide+One',
                    'content' => '',
                    'captionheader' => 'Caption 1'
                ),
                array (
                    'order' => 1,
                    'background-image' => 'http://placehold.it/1900x250/4e5d9d&text=Slide+Two',
                    'content' => '',
                    'captionheader' => 'Caption 2'
                )
            );
        }

		$options['slot'] = $slot;
		$options['public'] = true;

        $view['slots']->set($slot, $view->render('MauticPageBundle:Page:Slots/slideshow.html.php', $options));
    }
}
