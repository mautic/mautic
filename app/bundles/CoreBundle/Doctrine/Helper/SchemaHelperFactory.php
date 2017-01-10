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
 */
class SchemaHelperFactory
{
    /**
     * @var array
     */
    protected $helpers = [];

    /**
     * SchemaHelperFactory constructor.
     *
     * @param TableSchemaHelper  $tableSchemaHelper
     * @param IndexSchemaHelper  $indexSchemaHelper
     * @param ColumnSchemaHelper $columnSchemaHelper
     */
    public function __construct(TableSchemaHelper $tableSchemaHelper, IndexSchemaHelper $indexSchemaHelper, ColumnSchemaHelper $columnSchemaHelper)
    {
        $this->helpers['table']  = $tableSchemaHelper;
        $this->helpers['index']  = $indexSchemaHelper;
        $this->helpers['column'] = $columnSchemaHelper;
    }

    /**
     * Get a schema helper.
     *
     * @param $type
     * @param null $name
     *
     * @return mixed
     */
    public function getSchemaHelper($type, $name = null)
    {
        if (!array_key_exists($type, $this->helpers)) {
            throw new \InvalidArgumentException(sprintf('The requested helper (%s) is not a valid schema helper.', $type));
        }

        if ($name && method_exists($this->helpers[$type], 'setName')) {
            $this->helpers[$type]->setName($name);
        }

        return $this->helpers[$type];
    }
}
