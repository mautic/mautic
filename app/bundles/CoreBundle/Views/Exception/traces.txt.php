<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (count($exception['trace'])) {
    foreach ($exception['trace'] as $trace) {
        echo $view->render('MauticCoreBundle:Exception:trace.txt.php', array(
            'trace' => $trace
        ));
    }
}
