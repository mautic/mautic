<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Chart;

use Doctrine\DBAL\Connection;

/**
 * Class ChartQuery
 * 
 * Methods to get the chart data as native queries to get better performance and work with date/time native SQL queries.
 */
class ChartQuery
{
    protected $connection;

    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }

    /**
     * Fetch data for a time related dataset
     *
     * @param  string     $table without prefix
     * @param  string     $column name
     * @param  array      $filters will be added to where claues
     * @param  array      $options for special behavior
     */
    public function count($table, $column, $filters = array(), $options = array()) {
        $query = $this->connection->createQueryBuilder();

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
            $uniqueQuery = $this->connection->createQueryBuilder();
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
