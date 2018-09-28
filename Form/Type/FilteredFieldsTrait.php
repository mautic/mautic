<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Form\Type;

use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;

trait FilteredFieldsTrait
{
    /**
     * @var array
     */
    private $requiredFields = [];

    /**
     * @var int
     */
    private $totalFieldCount = 0;

    /**
     * @var array
     */
    private $filteredFields = [];

    /**
     * @param ConfigFormSyncInterface $integrationObject
     * @param string                  $objectName
     * @param string|null             $keyword
     * @param int                     $page
     */
    private function filterFields(ConfigFormSyncInterface $integrationObject, string $objectName, string $keyword = null, int $page = 1)
    {
        $this->requiredFields = $integrationObject->getRequiredFieldsForMapping($objectName);
        asort($this->requiredFields);

        $optionalFields = $integrationObject->getOptionalFieldsForMapping($objectName);
        asort($optionalFields);

        $allFields = array_merge($this->requiredFields, $optionalFields);

        if ($keyword) {
            $this->filteredFields = $this->getFieldsByKeyword($allFields, $keyword);

            // Paginate filtered fields
            $this->totalFieldCount = count($this->filteredFields);
            $this->filteredFields  = $this->getPageOfFields($this->filteredFields, $page);
        } else {
            $this->filteredFields  = $this->getPageOfFields($allFields, $page);
            $this->totalFieldCount = count($allFields);
        }
    }

    /**
     * @param array $fields
     * @param int   $page
     * @param int   $limit
     *
     * @return array
     */
    private function getPageOfFields(array $fields, int $page, int $limit = 15)
    {
        $offset = ($page - 1) * $limit;

        return array_slice($fields, $offset, $limit, true);
    }

    /**
     * @param array  $fields
     * @param string $keyword
     *
     * @return array
     */
    private function getFieldsByKeyword(array $fields, string $keyword)
    {
        $found = [];

        foreach ($fields as $name => $label) {
            if (!stristr($label, $keyword)) {
                continue;
            }

            $found[$name] = $label;
        }

        return $found;
    }

    /**
     * @return mixed
     */
    private function getRequiredFields()
    {
        return $this->requiredFields;
    }

    /**
     * @return mixed
     */
    private function getTotalFieldCount()
    {
        return $this->totalFieldCount;
    }

    /**
     * @return mixed
     */
    private function getFilteredFields()
    {
        return $this->filteredFields;
    }
}
