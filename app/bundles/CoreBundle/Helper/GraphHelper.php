<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

class GraphHelper
{
    /**
     * Get proper date label format depending on what date scope we want to display
     *
     * @param char $unit: php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @return string
     */
    public static function getDateLabelFromat($unit = 'D')
    {
        $format = '';
        if ($unit == 'H') {
            $format = 'H:00';
        } elseif ($unit == 'D') {
            $format = 'jS F';
        } elseif ($unit == 'W') {
            $format = 'W';
        } elseif ($unit == 'M') {
            $format = 'F y';
        } elseif ($unit == 'Y') {
            $format = 'Y';
        }
        return $format;
    }

    /**
     * Prepares data structure of labels and values needed for line graph.
     * fromDate variable can be used for SQL query as a limit.
     *
     * @param integer $amount of units
     * @param char    $unit: php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @return array
     */
    public static function prepareLineGraphData($amount = 30, $unit = 'D')
    {
        $isTime = '';

        if ($unit == 'H') {
            $isTime = 'T';
        }

        $format = self::getDateLabelFromat($unit);

        $date = new \DateTime();
        $oneUnit = new \DateInterval('P'.$isTime.'1'.$unit);
        $data = array('labels' => array(), 'values' => array());

        // Prefill $data arrays
        for ($i = 0; $i < $amount; $i++) {
            $data['labels'][$i] = $date->format($format);
            $data['values'][$i] = 0;
            $date->sub($oneUnit);
        }

        $data['fromDate'] = $date;

        return $data;
    }

    /**
     * Fills into graph data values grouped by time unit
     *
     * @param array  $graphData from prepareDownloadsGraphDataBefore
     * @param array  $items from database
     * @param char   $unit: php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     * @param string $dateName from database
     *
     * @return array
     */
    public static function mergeLineGraphData($graphData, $items, $unit, $dateName)
    {
        // Group hits by date
        foreach ($items as $item) {
            if (is_string($item[$dateName])) {
                $item[$dateName] = new \DateTime($item[$dateName]);
            }

            $oneItem = $item[$dateName]->format(self::getDateLabelFromat($unit));
            if (($itemKey = array_search($oneItem, $graphData['labels'])) !== false) {
                $graphData['values'][$itemKey]++;
            }
        }

        $graphData['values'] = array_reverse($graphData['values']);
        $graphData['labels'] = array_reverse($graphData['labels']);

        return $graphData;
    }
}
