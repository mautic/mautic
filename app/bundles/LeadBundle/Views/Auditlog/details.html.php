<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$details = $event['details'];
$type    = $event['eventType'];
$text    = '';
switch ($type) {
    case 'create':
        $text = 'The contact was created.';
        if (isset($details['ipAddresses']) && is_array($details['ipAddresses'])) {
            $text .= ' Origin IP: '.implode(', ', array_splice($details['ipAddresses'], 1));
        }
        break;
    case 'delete':
        $text = 'The contact was deleted.';
        break;
    case 'update':
        $text = 'The contact was updated.';
        if (!isset($details['fields'])) {
            break;
        }
        $text = '<table class="table">';
        $text .= '<tr>';
        $text .= '<th>Field</th><th>New Value</th><th>Old Value</th>';
        $text .= '</tr>';
        foreach ($details['fields'] as $field => $values) {
            $text .= '<tr>';
            $text .= "<td>$field</td><td>${values[1]}</td><td>${values[0]}</td>";
            $text .= '</tr>';
        }
        $text .= '</table>';
        break;
    case 'identified':
        $text = 'The contact was identified.';
        break;
    case 'ipadded':
        $text = 'The contact was accessed from: '.implode(',', array_splice($details, 1));
        break;
    case 'merged':
        $text = 'The contact was merged.';
        break;
}
echo $text;
echo '<!-- '.PHP_EOL.json_encode($details, JSON_PRETTY_PRINT).PHP_EOL.' -->';
