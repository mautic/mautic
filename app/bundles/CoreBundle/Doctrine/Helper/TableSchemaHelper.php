<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;

/**
 * Class TableSchemaHelper
 *
 * Used to manipulate creation/removal of tables
 *
 * @package Mautic\CoreBundle\Doctrine\Helper
 */
class TableSchemaHelper
{

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $sm;

    /**
     * @var
     */
    protected $prefix;

    /**
     * @var ColumnSchemaHelper
     */
    protected $columnHelper;

    /**
     * @param Connection $db
     * @param            $prefix
     */
    public function __construct(Connection $db, $prefix, ColumnSchemaHelper $columnHelper)
    {
        $this->db            = $db;
        $this->sm            = $db->getSchemaManager();
        $this->prefix        = $prefix;
        $this->columnHelper  = $columnHelper;
    }

    /**
     * Get the SchemaManager
     *
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getSchemaManager()
    {
        return $this->sm;
    }

    /**
     * Determine if a table exists
     *
     * @param      $table
     * @param bool $throwException
     * @return bool
     */
    public function checkTableExists($table, $throwException = false)
    {
        if (!$this->sm->tablesExist($this->prefix . $table)) {
            if ($throwException) {
                throw new \InvalidArgumentException($this->prefix . "$table does not exist");
            } else {
                return false;
            }
        } else {
            return true;
        }
    }
}