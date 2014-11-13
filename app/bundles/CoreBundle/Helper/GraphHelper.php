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
     * Colors which can be used in graphs
     */
    public static $colors = array(
        array(
            'color' => '#4E5D9D',
            'highlight' => '#353F6A',
            'fill' => 'rgba(78, 93, 157, 0.2)'),
        array(
            'color' => '#00b49c',
            'highlight' => '#007A69',
            'fill' => 'rgba(0, 180, 156, 0.2)'),
        array(
            'color' => '#fd9572',
            'highlight' => '#D53601',
            'fill' => 'rgba(253, 149, 114, 0.2)'),
        array(
            'color' => '#fdb933',
            'highlight' => '#D98C0A',
            'fill' => 'rgba(253, 185, 51, 0.2)')
    );

    /**
     * Time on site labels
     */
    public static $timesOnSite = array(
        array(
            'label' => '< 1m',
            'value' => 0,
            'from' => 0,
            'till' => 60),
        array(
            'label' => '1 - 5m',
            'value' => 0,
            'from' => 60,
            'till' => 300),
        array(
            'label' => '5 - 10m',
            'value' => 0,
            'from' => 300,
            'till' => 600),
        array(
            'label' => '> 10m',
            'value' => 0,
            'from' => 600,
            'till' => 999999),
    );

    public static function getTimesOnSite()
    {
        $timesOnSite = self::$timesOnSite;
        $colors = self::$colors;
        foreach ($timesOnSite as $key => $tos) {
            if (isset($colors[$key])) {
                $timesOnSite[$key]['color'] = $colors[$key]['color'];
                $timesOnSite[$key]['highlight'] = $colors[$key]['highlight'];
            } else {
                $timesOnSite[$key]['color'] = '#4E5D9D';
                $timesOnSite[$key]['highlight'] = '#353F6A';
            }
        }
        return $timesOnSite;
    }

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
            $format = 'l ga';
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
    public static function prepareLineGraphData($amount = 30, $unit = 'D', $datasetLabels = array('Dataset 1'))
    {
        $isTime = '';

        if ($unit == 'H') {
            $isTime = 'T';
        }

        $format = self::getDateLabelFromat($unit);

        $date = new \DateTime();
        $oneUnit = new \DateInterval('P'.$isTime.'1'.$unit);
        $data = array('labels' => array(), 'datasets' => array());
        $j = 0;

        // Prefill $data arrays
        foreach ($datasetLabels as $key => $label) {
            if (!isset(self::$colors[$j])) {
                $j = 0;
            }

            $data['datasets'][$key] = array(
                'label' => $label,
                'fillColor' => self::$colors[$j]['fill'],
                'highlightFill' => self::$colors[$j]['color'],
                'strokeColor' => self::$colors[$j]['highlight'],
                'pointColor' => self::$colors[$j]['highlight'],
                'pointStrokeColor' => '#fff',
                'pointHighlightFill' => '#fff',
                'pointHighlightStroke' => self::$colors[$j]['highlight'],
                'data' => array()
            );
            $j++;
            for ($i = 0; $i < $amount; $i++) {
                if ($key === 0) {
                    $data['labels'][$i] = $date->format($format);
                    $date->sub($oneUnit);
                }
                $data['datasets'][$key]['data'][$i] = 0;
            }
        }

        $data['fromDate'] = $date;

        $data['labels'] = array_reverse($data['labels']);

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
    public static function mergeLineGraphData($graphData, $items, $unit, $datasetKey, $dateName, $deltaName = null, $average = false)
    {
        if ($average) {
            $graphData['datasets'][$datasetKey]['count'] = array();
        }

        foreach ($items as $item) {
            if (isset($item[$dateName]) && $item[$dateName]) {
                if (is_string($item[$dateName])) {
                    $item[$dateName] = new \DateTime($item[$dateName]);
                }

                $oneItem = $item[$dateName]->format(self::getDateLabelFromat($unit));
                if (($itemKey = array_search($oneItem, $graphData['labels'])) !== false) {
                    if ($deltaName) {
                        if ($average) {
                            if (isset($graphData['datasets'][$datasetKey]['count'][$itemKey])) {
                                $graphData['datasets'][$datasetKey]['count'][$itemKey]++;
                            } else {
                                $graphData['datasets'][$datasetKey]['count'][$itemKey] = 1;
                            }
                        }
                        $graphData['datasets'][$datasetKey]['data'][$itemKey] += $item[$deltaName];
                    } else {
                        $graphData['datasets'][$datasetKey]['data'][$itemKey]++;
                    }
                }
            }
        }

        if ($average) {
            foreach ($graphData['datasets'][$datasetKey]['data'] as $key => $value) {
                if (isset($graphData['datasets'][$datasetKey]['count'][$key]) && $graphData['datasets'][$datasetKey]['count'][$key]) {
                    $graphData['datasets'][$datasetKey]['data'][$key] /= $graphData['datasets'][$datasetKey]['count'][$key];
                }
            }
        }

        unset($graphData['fromDate']);

        return $graphData;
    }

    /**
     * Fills into Pie graph data values from database
     *
     * @param array  $data from database
     *
     * @return array
     */
    public static function preparePieGraphData($data)
    {
        $colors = self::$colors;
        $graphData = array();
        $i = 0;
        $suma = 0;

        foreach($data as $count) {
            $suma += $count;
        }

        foreach($data as $label => $count) {

            if (!isset($colors[$i])) {
                $i = 0;
            }

            $percent = 0;

            if ($suma > 0) {
                $percent = $count / $suma * 100;
            }
            
            $color = $colors[$i];
            $graphData[] = array(
                'label' => $label,
                'color' => $colors[$i]['color'],
                'highlight' => $colors[$i]['highlight'],
                'value' => (int) $count,
                'percent' => $percent
            );
            $i++;
        }

        return $graphData;
    }
}
