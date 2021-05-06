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

class BatchIdToEntityHelper
{
    /**
     * @var array
     */
    private $ids = [];

    /**
     * @var array
     */
    private $originalKeys = [];

    /**
     * @var string
     */
    private $idKey;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var bool
     */
    private $isAssociative = false;

    /**
     * BatchIdToEntityHelper constructor.
     *
     * @param string $idKey
     */
    public function __construct(array $parameters, $idKey = 'id')
    {
        $this->idKey = $idKey;

        $this->extractIds($parameters);
    }

    /**
     * @return bool
     */
    public function hasIds()
    {
        return !empty($this->ids);
    }

    /**
     * @return array
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Reorder the entities based on the original keys
     * BC allowed a request to have associative keys (don't ask why; yes it's terrible implementation but we're keeping BC here)
     * The issue this solves is the response should match the format given by the request. If the request had associative keys, the response
     * will return with associative keys (json object). If the request was a sequential numeric array starting with 0, the response will
     * be a simple array (json array).
     *
     * @return array
     */
    public function orderByOriginalKey(array $entities)
    {
        if (!$this->isAssociative) {
            // The request was keyed by sequential numbers starting with 0
            return array_values($entities);
        }

        // Ensure entities are keyed by ID in order to find the original keys assuming that some entities are missing if the ID was not found
        $entitiesKeyedById = [];
        foreach ($entities as $entity) {
            $entitiesKeyedById[$entity->getId()] = $entity;
        }

        $orderedEntities = [];
        foreach ($this->ids as $key => $id) {
            if (!isset($entitiesKeyedById[$id])) {
                continue;
            }

            $originalKey                   = $this->originalKeys[$key];
            $orderedEntities[$originalKey] = $entitiesKeyedById[$id];
        }

        return $orderedEntities;
    }

    private function extractIds(array $parameters)
    {
        $this->ids = [];

        if (isset($parameters['ids'])) {
            $this->extractIdsFromIdKey($parameters['ids']);

            return;
        }

        $this->extractIdsFromParams($parameters);
    }

    /**
     * @param mixed $ids
     */
    private function extractIdsFromIdKey($ids)
    {
        // ['ids' => [1,2,3]]
        if (is_array($ids)) {
            $this->isAssociative = $this->isAssociativeArray($ids);
            $this->ids           = array_values($ids);
            $this->originalKeys  = array_keys($ids);

            return;
        }

        // ['ids' => '1,2,3']
        if (false !== strpos($ids, ',')) {
            $this->ids           = str_getcsv($ids);
            $this->originalKeys  = array_keys($this->ids);
            $this->isAssociative = false;

            return;
        }

        // Couldn't parse the 'ids' key; not throwing an exception in order to keep BC with
        // the old CommonApiController code and the use of a foreach in extractIdsFromParams
        $this->errors[] = 'mautic.api.call.id_missing';
    }

    private function extractIdsFromParams(array $parameters)
    {
        $this->isAssociative = $this->isAssociativeArray($parameters);
        $this->originalKeys  = array_keys($parameters);

        // [1,2,3]
        reset($parameters);
        $firstKey = key($parameters);
        if (!is_array($parameters[$firstKey])) {
            $this->ids = array_values($parameters);

            return;
        }

        // [ ['id' => 1, 'foo' => 'bar'], ['id' => 2, 'bar' => 'foo'] ]
        foreach ($parameters as $key => $params) {
            // Missing id column key in the array; terrible but keep BC
            if (!isset($params[$this->idKey])) {
                $this->errors[$key] = 'mautic.api.call.id_missing';

                continue;
            }

            $this->ids[] = $params[$this->idKey];
        }
    }

    /**
     * @return bool
     */
    private function isAssociativeArray(array $array)
    {
        if (empty($array)) {
            return false;
        }

        reset($array);
        $firstKey = key($array);

        return array_keys($array) !== range(0, count($array) - 1) && 0 !== $firstKey;
    }

    public function setIsAssociative(bool $isAssociative): void
    {
        $this->isAssociative = $isAssociative;
    }
}
