<?php

declare(strict_types=1);

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
use MauticPlugin\IntegrationsBundle\Mapping\MappedFieldInfoInterface;

trait FilteredFieldsTrait
{
    /**
     * @var int
     */
    private $totalFieldCount = 0;

    /**
     * @var array|MappedFieldInfoInterface[]
     */
    private $filteredFields = [];

    /**
     * @param ConfigFormSyncInterface $integrationObject
     * @param string                  $objectName
     * @param string|null             $keyword
     * @param int                     $page
     */
    private function filterFields(ConfigFormSyncInterface $integrationObject, string $objectName, string $keyword = null, int $page = 1): void
    {
        $allFields = $integrationObject->getAllFieldsForMapping($objectName);

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
     * @return array|MappedFieldInfoInterface[]
     */
    private function getPageOfFields(array $fields, int $page, int $limit = 15): array
    {
        $offset = ($page - 1) * $limit;

        return array_slice($fields, $offset, $limit, true);
    }

    /**
     * @param array|MappedFieldInfoInterface[] $fields
     * @param string                           $keyword
     *
     * @return array|MappedFieldInfoInterface[]
     */
    private function getFieldsByKeyword(array $fields, string $keyword): array
    {
        $found = [];

        foreach ($fields as $name => $field) {
            if (!stristr($field->getName(), $keyword)) {
                continue;
            }

            $found[$name] = $field;
        }

        return $found;
    }

    /**
     * @return int
     */
    private function getTotalFieldCount(): int
    {
        return $this->totalFieldCount;
    }

    /**
     * @return array|MappedFieldInfoInterface[]
     */
    private function getFilteredFields(): array
    {
        return $this->filteredFields;
    }
}
