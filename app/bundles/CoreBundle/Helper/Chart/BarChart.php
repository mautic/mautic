<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\AbstactChart;
use Mautic\CoreBundle\Helper\Chart\ChartInterface;
use Doctrine\DBAL\Connection;

/**
 * Class BarChart
 */
class BarChart extends AbstractChart implements ChartInterface
{
    protected $unit;
    protected $limit;
    protected $start;
    protected $order;

    /**
     * Render chart data
     */
    public function __construct($unit = 'm', $limit = 12, $start = null, $order = 'DESC') {
        $this->unit = $unit;
        $this->limit = $limit;
        $this->start = $start;
        $this->order = $order;
        $this->generateTimeLabels($unit, $limit, $start, $order);
    }

    /**
     * Render chart data
     */
    public function render() {
        return array(
            'labels'   => $this->labels,
            'datasets' => $this->datasets
        );
    }

    /**
     * Define a dataset by name and data. Method will add the rest.
     *
     * @param  string $label
     * @param  array  $data
     *
     * @return $this
     */
    public function setDataset($label = null, array $data)
    {
        $datasetId = count($this->datasets);

        $baseData = array(
            'label' => $label,
            'data'  => $data,
        );

        $this->datasets[] = array_merge($baseData, $this->colors[$datasetId]);

        return $this;
    }

    /**
     * Fetch data for a time related dataset
     *
     * @param  Connection $connection
     * @param  string     $table without prefix
     * @param  string     $dateColumn name. The column must by type of datetime
     * @param  array      $filters will be added to where claues
     */
    public function fetchTimeData(Connection $connection, $table, $dateColumn, $filters = array()) {
        // Convert time unitst to the right form for current database platform
        $dbUnit = $this->translateTimeUnit($connection, $this->unit);
        $query = $connection->createQueryBuilder();

        // Postgres and MySql are handeling date/time SQL funciton differently
        if ($this->isPostgres($connection)) {
            $query->select('DATE_TRUNC(\'' . $dbUnit . '\', t.' . $dateColumn . ') AS date, COUNT(t) AS count')
                ->from(MAUTIC_TABLE_PREFIX . $table, 't')
                ->groupBy('DATE_TRUNC(\'' . $dbUnit . '\', t.' . $dateColumn . ')')
                ->orderBy('DATE_TRUNC(\'' . $dbUnit . '\', t.' . $dateColumn . ')', $this->order);
        } elseif ($this->isMysql($connection)) {
            $query->select('DATE_FORMAT(t.' . $dateColumn . ', \'' . $dbUnit . '\') AS date, COUNT(t) AS count')
                ->from(MAUTIC_TABLE_PREFIX . $table, 't')
                ->groupBy('DATE_FORMAT(t.' . $dateColumn . ', \'' . $dbUnit . '\')')
                ->orderBy('DATE_FORMAT(t.' . $dateColumn . ', \'' . $dbUnit . '\'', $this->order);
        } else {
            throw new UnexpectedValueException(__CLASS__ . '::' . __METHOD__ . ' supports only MySql a Posgress database platforms.');
        }

        // Apply start date/time if set
        if ($this->start) {
            $query->andWhere('t.' . $dateColumn . ' <= :startdate');
            $query->setParameter('startdate', $this->start);
        }

        // Apply filters
        foreach ($filters as $column => $value) {
            $valId = $column . '_val';
            if (is_array($value)) {
                $query->andWhere('t.' . $column . ' IN(:' . $valId . ')');
                $query->setParameter($valId, implode(',', $value));
            } else {
                $query->andWhere('t.' . $column . ' = :' . $valId);
                $query->setParameter($valId, $value);
            }
        }

        $query->setMaxResults($this->limit);

        // Fetch the data
        $rawData = $query->execute()->fetchAll();

        $data    = array();
        $date    = new \DateTime((new \DateTime($this->start))->format($this->sqlFormats[$this->unit]));
        $oneUnit = $this->getUnitObject($this->unit);

        // Convert data from DB to the chart.js format
        for ($i = 0; $i < $this->limit; $i++) {

            $nextDate = clone $date;
            if ($this->order == 'DESC') {
                $nextDate->sub($oneUnit);
            } else {
                $nextDate->add($oneUnit);
            }

            foreach ($rawData as $key => $item) {
                $itemDate = new \DateTime($item['date']);

                // The right value is between the time unit and time unit +1 for ASC ordering
                if ($this->order == 'ASC' && $itemDate >= $date && $itemDate < $nextDate) {
                    $data[$i] = $item['count'];
                    unset($rawData[$key]);
                    continue;
                }

                // The right value is between the time unit and time unit -1 for DESC ordering
                if ($this->order == 'DESC' && $itemDate <= $date && $itemDate > $nextDate) {
                    $data[$i] = $item['count'];
                    unset($rawData[$key]);
                    continue;
                }
            }

            // Chart.js requires the 0 for empty data, but the array slot has to exist
            if (!isset($data[$i])) {
                $data[$i] = 0;
            }

            if ($this->order == 'DESC') {
                $date->sub($oneUnit);
            } else {
                $date->add($oneUnit);
            }
        }

        return  array_reverse($data);
    }
}
