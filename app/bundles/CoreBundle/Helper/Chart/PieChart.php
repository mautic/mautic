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
 * Class PieChart
 */
class PieChart extends AbstractChart implements ChartInterface
{
    protected $totalCount = 0;

    /**
     * Render chart data
     */
    public function render() {

        // Add numbers to the labels
        if ($this->totalCount) {
            foreach ($this->datasets as &$dataset) {
                $percentige = round($dataset['value'] / $this->totalCount * 100, 2);
                $dataset['label'] .= '; ' . $dataset['value'] . 'x, ' . $percentige . '%';
            }
        }

        return $this->datasets;
    }

    /**
     * Define a dataset by name and count number. Method will add the rest.
     *
     * @param  string  $label
     * @param  integer $value
     *
     * @return $this
     */
    public function setDataset($label, $value)
    {
        $this->totalCount += $value;
        $datasetId = count($this->datasets);

        $this->datasets[] = array(
            'label'     => $label,
            'value'     => $value,
            'color'     => $this->colors[$datasetId]['highlightFill'],
            'highlight' => $this->colors[$datasetId]['highlightStroke']
        );

        return $this;
    }

    /**
     * Fetch data for a time related dataset
     *
     * @param  Connection $connection
     * @param  string     $table without prefix
     * @param  string     $column name
     * @param  array      $filters will be added to where claues
     * @param  array      $options for special behavior
     */
    public function count(Connection $connection, $table, $column, $filters = array(), $options = array()) {
        $query = $connection->createQueryBuilder();

        $query->select('COUNT(t.' . $column . ') AS count')
            ->from(MAUTIC_TABLE_PREFIX . $table, 't');

        // Apply filters
        foreach ($filters as $whereColumn => $value) {
            $valId = $whereColumn . '_val';
            if (is_array($value)) {
                $query->andWhere('t.' . $whereColumn . ' IN(:' . $valId . ')');
                $query->setParameter($valId, implode(',', $value));
            } else {
                $query->andWhere('t.' . $whereColumn . ' = :' . $valId);
                $query->setParameter($valId, $value);
            }
        }

        // Count only unique values
        if (!empty($options['getUnique'])) {
            // Modify the previous query
            $query->select('t.' . $column . '');
            $query->having('COUNT(*) = 1')
                ->groupBy('t.' . $column);

            // Create a new query with subquery of the previous query
            $uniqueQuery = $connection->createQueryBuilder();
            $uniqueQuery->select('COUNT(t.' . $column . ') AS count')
                ->from('(' . $query->getSql() . ')', 't');

            // Apply params from the previous query to the new query
            $uniqueQuery->setParameters($query->getParameters());

            // Replace the new query with previous query
            $query = $uniqueQuery;
        }

        // Fetch the count
        $data = $query->execute()->fetch();

        return $data['count'];
    }
}
