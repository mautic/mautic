<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($trace['function']) :
    echo ' at ' . $trace['class'] . ' ' . $trace['type'] . ' ' . $trace['function'] . '(' . $view['exception']->formatArgsAsText($trace['args']) . ')';
else :
    echo 'n/a';
endif;

if (isset($trace['file']) && isset($trace['line'])) :
    echo ' in ' . $trace['file'] . ' line ' . $trace['line'];
endif;
