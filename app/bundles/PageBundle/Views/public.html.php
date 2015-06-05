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

if ($code = $view['analytics']->getCode()) {
    $view['assets']->addCustomDeclaration($code);
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
                'background_color' => 'transparent',
                'arrow_navigation' => false,
                'dot_navigation' => true,
                'interval' => 5000,
                'pause' => 'hover',
                'wrap' => true,
                'keyboard' => true
            );
		}

		// Create sample slides for first time or if all slides were deleted
        if (empty($options['slides'])) {
            $options['slides'] =  array (
                array (
                    'order' => 0,
                    'background-image' => $view['assets']->getUrl('media/images/mautic_logo_lb200.png'),
                    'captionheader' => 'Caption 1'
                ),
                array (
                    'order' => 1,
                    'background-image' => $view['assets']->getUrl('media/images/mautic_logo_db200.png'),
                    'captionheader' => 'Caption 2'
                )
            );
        }

        // Order slides
        usort($options['slides'], function($a, $b)
        {
            return strcmp($a['order'], $b['order']);
        });

		$options['slot'] = $slot;
		$options['public'] = true;

        $view['slots']->set($slot, $view->render('MauticPageBundle:Page:Slots/slideshow.html.php', $options));
    }
}
