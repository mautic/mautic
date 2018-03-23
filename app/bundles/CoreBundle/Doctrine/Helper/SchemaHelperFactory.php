<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Helper;

/**
 * Class SchemaHelperFactory.
 *
 * @deprecated 2.13.0; to be removed in 3.0. Use the appropriate schema helper service instead.
 */
class SchemaHelperFactory
{
    const TYPE_TABLE  = 'table';
    const TYPE_INDEX  = 'index';
    const TYPE_COLUMN = 'column';

    /**
     * @var TableSchemaHelper
     */
    private $tableHelper;

    /**
     * @var ColumnSchemaHelper
     */
    private $columnHelper;

    /**
     * @var IndexSchemaHelper
     */
    private $indexHelper;

    /**
     * SchemaHelperFactory constructor.
     *
     * @param TableSchemaHelper  $tableSchemaHelper
     * @param IndexSchemaHelper  $indexSchemaHelper
     * @param ColumnSchemaHelper $columnSchemaHelper
     */
    public function __construct(TableSchemaHelper $tableSchemaHelper, IndexSchemaHelper $indexSchemaHelper, ColumnSchemaHelper $columnSchemaHelper)
    {
        $this->tableHelper  = $tableSchemaHelper;
        $this->indexHelper  = $indexSchemaHelper;
        $this->columnHelper = $columnSchemaHelper;
    }

    /**
     * @param      $type
     * @param null $name
     *
     * @return ColumnSchemaHelper|IndexSchemaHelper|TableSchemaHelper
     */
    public function getSchemaHelper($type, $name = null)
    {
        switch ($type) {
            case self::TYPE_COLUMN:
                $helper = $this->columnHelper;
                break;
            case self::TYPE_INDEX:
                $helper = $this->indexHelper;
                break;
            case self::TYPE_TABLE:
                $helper = $this->tableHelper;
                break;
            default:
                throw new \InvalidArgumentException(sprintf('The requested helper (%s) is not a valid schema helper.', $type));
        }

        if ($name && method_exists($helper, 'setName')) {
            $helper->setName($name);
        }

        return $helper;
    }

    /**
     * @param null $name
     *
     * @return ColumnSchemaHelper
     */
    public function getColumnHelper($name = null)
    {
        return $this->getSchemaHelper(self::TYPE_COLUMN, $name);
    }

    /**
     * @param null $name
     *
     * @return IndexSchemaHelper
     */
    public function getIndexHelper($name = null)
    {
        return $this->getSchemaHelper(self::TYPE_INDEX, $name);
    }

    /**
     * @param null $name
     *
     * @return TableSchemaHelper
     */
    public function getTableHelper($name = null)
    {
        return $this->getSchemaHelper(self::TYPE_TABLE, $name);
    }
}
