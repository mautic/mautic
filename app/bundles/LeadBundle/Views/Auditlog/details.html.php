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
        $text = $view['translator']->trans('mautic.lead.audit.created');
        if (isset($details['ipAddresses']) && is_array($details['ipAddresses'])) {
            $text .= ' '.$view['translator']->trans('mautic.lead.audit.originip').' '.implode(', ', array_splice($details['ipAddresses'], 1));
        }
        break;
    case 'delete':
        $text = $view['translator']->trans('mautic.lead.audit.deleted');
        break;
    case 'update':
        $text = $view['translator']->trans('mautic.lead.audit.updated');
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
        $text = $view['translator']->trans('mautic.lead.audit.identified');
        break;
    case 'ipadded':
        $text = $view['translator']->trans('mautic.lead.audit.accessed').' '.implode(',', array_splice($details, 1));
        break;
    case 'merged':
        $text = $view['translator']->trans('mautic.lead.audit.merged');
        break;
}
echo $text;
echo '<!-- '.PHP_EOL.json_encode($details, JSON_PRETTY_PRINT).PHP_EOL.' -->';
