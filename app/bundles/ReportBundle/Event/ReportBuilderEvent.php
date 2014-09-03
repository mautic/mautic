<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Event;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ReportBuilderEvent
 *
 * @package Mautic\ReportBundle\Event
 */
class ReportBuilderEvent extends Event
{
    /**
     * Container with all registered tables and columns
     *
     * @var array
     */
    private $tableArray = array();

    /**
     * Add a table with the specified columns to the lookup
     *
     * @param string $tableName Table to add
     * @param array  $columns   Array of columns to add
     *
     * @return void
     */
    public function addTable($tableName, array $columns)
    {
        $this->tableArray[$table] = $columns;
    }

    /**
     * Fetch the tables in the lookup array
     *
     * @return array
     */
    public function getTables()
    {
        return $this->tableArray;
    }

    /**
     * Remove a table from the lookup array
     *
     * @param string $tableName Table to remove
     *
     * @return void
     */
    public function removeTable($tableName)
    {
        if (isset($this->tableArray[$tableName])) {
            unset($this->tableArray[$tableName]);
        }
    }

    /**
     * Add a column to the specified table
     *
     * @param string $tableName Table to add the column to
     * @param string $column    Column to add
     *
     * @return void
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException If table is not registered
     */
    public function addColumn($tableName, $column)
    {
        if (!array_key_exists($tableName, $this->tableArray)) {
            throw new InvalidArgumentException(sprintf('The %s table is not set.', $tableName));
        }

        $this->tableArray[$table][] = $column;
    }

    /**
     * Remove a column from the specified table
     *
     * @param string $tableName Table to remove the column from
     * @param string $column    Column to remove
     *
     * @return void
     */
    public function removeColumn($tableName, $column)
    {
        if (($key = array_search($column, $this->tableArray[$tableName])) !== false) {
            unset($this->tableArray[$tableName][$key]);
        }
    }
}
