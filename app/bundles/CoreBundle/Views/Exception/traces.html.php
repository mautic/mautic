<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$formatArgs = function ($args) use (&$formatArgs) {
    $result = [];
    foreach ($args as $key => $item) {
        if ('object' === $item[0]) {
            $parts          = explode('\\', $item[1]);
            $short          = array_pop($parts);
            $formattedValue = sprintf('<em>object</em>(<abbr title="%s">%s</abbr>)', $item[1], $short);
        } elseif ('array' === $item[0]) {
            $formattedValue = sprintf('<em>array</em>(%s)', is_array($item[1]) ? $formatArgs($item[1]) : $item[1]);
        } elseif ('string' === $item[0]) {
            $formattedValue = sprintf("'%s'", htmlspecialchars($item[1]));
        } elseif ('null' === $item[0]) {
            $formattedValue = '<em>null</em>';
        } elseif ('boolean' === $item[0]) {
            $formattedValue = '<em>'.strtolower(var_export($item[1], true)).'</em>';
        } elseif ('resource' === $item[0]) {
            $formattedValue = '<em>resource</em>';
        } else {
            $formattedValue = str_replace("\n", '', var_export(htmlspecialchars((string) $item[1]), true));
        }

        $result[] = is_int($key) ? $formattedValue : sprintf("'%s' => %s", $key, $formattedValue);
    }

    return implode(', ', $result);
};

echo "<ol>\n";
foreach ($traces as $trace) {
    echo '<li class="pt-3 break-word">';
    echo "<strong>{$trace['file']}</strong>";
    if ($trace['line']) {
        echo ' (line #'.$trace['line'].'): ';
    }
    if (!empty($trace['function'])) {
        echo "at {$trace['class']} {$trace['type']} {$trace['function']} ( ";
        if (!empty($trace['args'])) {
            echo $formatArgs($trace['args']);
        }
        echo ' )';
    }
    echo '</li>';
}
echo "</ol>\n";
