<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

/**
 * Class GraphHelper
 */
class GraphHelper
{
    /**
     * Colors which can be used in graphs
     *
     * @var array
     */
    public static $colors = array(
        array(
            'color' => '#4E5D9D',
            'fill'   => 'rgba(78, 93, 157, 0.5)',
            'fillStroke' => 'rgba(78, 93, 157, 0.8)',
            'highlight' => 'rgba(78, 93, 157, 0.75)',
            'highlightStroke' => 'rgba(78, 93, 157, 1)'),
        array(
            'color' => '#00b49c',
            'fill'   => 'rgba(0, 180, 156, 0.5)',
            'fillStroke' => 'rgba(0, 180, 156, 0.8)',
            'highlight' => 'rgba(0, 180, 156, 0.75)',
            'highlightStroke' => 'rgba(0, 180, 156, 1)'),
        array(
            'color' => '#fd9572',
            'fill'   => 'rgba(253, 149, 114, 0.5)',
            'fillStroke' => 'rgba(253, 149, 114, 0.8)',
            'highlight' => 'rgba(253, 149, 114, 0.75)',
            'highlightStroke' => 'rgba(253, 149, 114, 1)'),
        array(
            'color' => '#fdb933',
            'fill'   => 'rgba(253, 185, 51, 0.5)',
            'fillStroke' => 'rgba(253, 185, 51, 0.8)',
            'highlight' => 'rgba(253, 185, 51, 0.75)',
            'highlightStroke' => 'rgba(253, 185, 51, 1)'),
        array(
            'color' => '#757575',
            'fill'   => 'rgba(117, 117, 117, 0.5)',
            'fillStroke' => 'rgba(117, 117, 117, 0.8)',
            'highlight' => 'rgba(117, 117, 117, 0.75)',
            'highlightStroke' => 'rgba(117, 117, 117, 1)'),
        array(
            'color' => '#9C4E5C',
            'fill'   => 'rgba(156, 78, 92, 0.5)',
            'fillStroke' => 'rgba(156, 78, 92, 0.8)',
            'highlight' => 'rgba(156, 78, 92, 0.75)',
            'highlightStroke' => 'rgba(156, 78, 92, 1)'),
        array(
            'color' => '#694535',
            'fill'   => 'rgba(105, 69, 53, 0.5)',
            'fillStroke' => 'rgba(105, 69, 53, 0.8)',
            'highlight' => 'rgba(105, 69, 53, 0.75)',
            'highlightStroke' => 'rgba(105, 69, 53, 1)'),
        array(
            'color' => '#596935',
            'fill'   => 'rgba(89, 105, 53, 0.5)',
            'fillStroke' => 'rgba(89, 105, 53, 0.8)',
            'highlight' => 'rgba(89, 105, 53, 0.75)',
            'highlightStroke' => 'rgba(89, 105, 53, 1)'),
    );

    /**
     * Time on site labels
     *
     * @var array
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

    /**
     * Get proper date label format depending on what date scope we want to display
     *
     * @param string $unit : php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @return string
     */
    public static function getDateLabelFormat($unit = 'D')
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
     * Prepares data structure of labels and values needed for datetime line graph.
     * fromDate variable can be used for SQL query as a limit.
     *
     * @param integer $amount        of units
     * @param string  $unit          php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     * @param array   $datasetLabels
     *
     * @return array
     */
    public static function prepareDatetimeLineGraphData($amount = 30, $unit = 'D', $datasetLabels = array('Dataset 1'))
    {
        $isTime = '';

        if ($unit == 'H') {
            $isTime = 'T';
        }

        $format = self::getDateLabelFormat($unit);

        $date    = new \DateTime();
        $oneUnit = new \DateInterval('P' . $isTime . '1' . $unit);
        $data    = array('labels' => array(), 'datasets' => array());
        $j       = 0;

        // Prefill $data arrays
        foreach ($datasetLabels as $key => $label) {
            if (!isset(self::$colors[$j])) {
                $j = 0;
            }

            $data['datasets'][$key] = array(
                'label'                => $label,
                'fillColor'            => self::$colors[$j]['fill'],
                'highlightFill'        => self::$colors[$j]['highlight'],
                'strokeColor'          => self::$colors[$j]['fillStroke'],
                'pointColor'           => self::$colors[$j]['color'],
                'pointStrokeColor'     => '#fff',
                'pointHighlightFill'   => '#fff',
                'pointHighlightStroke' => self::$colors[$j]['highlightStroke'],
                'data'                 => array()
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
     * Prepares data structure for basic count based line graphs
     *
     * @param array $labels       array of labels
     * @param array $datasets     array array(label => array of values in order of $labels)
     *
     * @return array
     */
    public static function prepareLineGraphData($labels, $datasets)
    {
        $data = array();
        $data['labels'] = $labels;
        $j = 0;
        foreach ($datasets as $label => $dataset) {
            if (!isset(self::$colors[$j])) {
                $j = 0;
            }

            $data['datasets'][$label] = array(
                'label'                => $label,
                'fillColor'            => self::$colors[$j]['fill'],
                'highlightFill'        => self::$colors[$j]['highlight'],
                'strokeColor'          => self::$colors[$j]['fillStroke'],
                'pointColor'           => self::$colors[$j]['color'],
                'pointStrokeColor'     => '#fff',
                'pointHighlightFill'   => '#fff',
                'pointHighlightStroke' => self::$colors[$j]['highlightStroke'],
                'data'                 => $dataset
            );
            $j++;
        }

        return $data;
    }

    /**
     * Prepares data structure for basic count based line graphs
     *
     * @param array $labels       array of labels
     * @param array $datasets     array array(label => array of values in order of $labels)
     *
     * @return array
     */
    public static function prepareBarGraphData($labels, $datasets)
    {
        $data = array();
        $data['labels'] = $labels;
        $j = 0;
        foreach ($datasets as $label => $dataset) {
            if (!isset(self::$colors[$j])) {
                $j = 0;
            }

            $data['datasets'][] = array(
                'label'                => $label,
                'fillColor'            => self::$colors[$j]['fill'],
                'highlightFill'        => self::$colors[$j]['highlight'],
                'strokeColor'          => self::$colors[$j]['fillStroke'],
                'data'                 => $dataset
            );
            $j++;
        }

        return $data;
    }

    /**
     * Fills into graph data values grouped by time unit
     *
     * @param array  $graphData from prepareDownloadsGraphDataBefore
     * @param array  $items     from database
     * @param string $unit      php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     * @param string $datasetKey
     * @param string $dateName  from database
     * @param string $deltaName
     * @param bool   $average
     * @param bool   $incremental
     *
     * @return array
     */
    public static function mergeLineGraphData($graphData, $items, $unit, $datasetKey, $dateName, $deltaName = null, $average = false, $incremental = false)
    {
        if ($average) {
            $graphData['datasets'][$datasetKey]['count'] = array();
        }

        $utc = new \DateTimeZone('UTC');
        foreach ($items as $item) {
            if (isset($item[$dateName]) && $item[$dateName]) {
                if (is_string($item[$dateName])) {
                    // Assume that it is UTC
                    $item[$dateName] = new \DateTime($item[$dateName], $utc);
                }

                $oneItem = $item[$dateName]->format(self::getDateLabelFormat($unit));
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

        if ($incremental) {
            $incrementalCount = 0;
            foreach ($graphData['datasets'][$datasetKey]['data'] as $key => $value) {
                if ($graphData['datasets'][$datasetKey]['data'][$key]) {
                    $incrementalCount += $graphData['datasets'][$datasetKey]['data'][$key];
                }

                $graphData['datasets'][$datasetKey]['data'][$key] = $incrementalCount;
            }
        }

        unset($graphData['fromDate']);

        return $graphData;
    }

    /**
     * Fills into Pie graph data values from database
     *
     * @param array $data
     *
     * @return array
     */
    public static function preparePieGraphData($data)
    {
        $colors    = self::$colors;
        $graphData = array();
        $i         = 0;
        $suma      = 0;

        foreach ($data as $count) {
            $suma += $count;
        }

        foreach ($data as $label => $count) {
            if (!isset($colors[$i])) {
                $i = 0;
            }

            $percent = 0;

            if ($suma > 0) {
                $percent = $count / $suma * 100;
            }

            $graphData[] = array(
                'label'     => $label,
                'color'     => $colors[$i]['color'],
                'highlight' => $colors[$i]['highlight'],
                'value'     => (int) $count,
                'percent'   => $percent
            );
            $i++;
        }

        return $graphData;
    }
}
