<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Helper;

use Doctrine\ORM\Tools\Pagination\Paginator;

class EntityResultHelper
{
    /**
     * @param mixed  $results
     * @param string $callback
     *
     * @return array
     */
    public function getArray($results, $callback = null)
    {
        $entities = [];

        // we have to convert them from paginated proxy functions to entities in order for them to be
        // returned by the serializer/rest bundle
        foreach ($results as $key => $entityRow) {
            $entities[$key] = $this->getEntityData($entityRow);

            if (is_callable($callback)) {
                $callback($entities[$key]);
            }
        }

        // solving array/object discrepancy for empty values
        if ($this->isKeyedById($results) && empty($entities)) {
            $entities = new \ArrayObject();
        }

        return $entities;
    }

    /**
     * @param mixed $entityRow
     *
     * @return mixed
     */
    private function getEntityData($entityRow)
    {
        if (is_array($entityRow) && isset($entityRow[0])) {
            return $this->getDataForArray($entityRow);
        }

        return $entityRow;
    }

    /**
     * @param array $array
     *
     * @return mixed
     */
    private function getDataForArray($array)
    {
        if (is_object($array[0])) {
            return $this->getDataForObject($array);
        }

        return $array[0];
    }

    /**
     * @param object $object
     *
     * @return mixed
     */
    private function getDataForObject($object)
    {
        foreach ($object as $key => $value) {
            if (0 === $key) {
                continue;
            }

            $object[0]->$key = $value;
        }

        return $object[0];
    }

    /**
     * @param mixed $results
     *
     * @return bool
     */
    private function isKeyedById($results)
    {
        if ($results instanceof Paginator) {
            return false;
        }

        return true;
    }
}
