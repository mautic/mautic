<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Deduplicate;

use Mautic\LeadBundle\Model\FieldModel;

trait DeduperTrait
{
    private $object = 'lead';

    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var array
     */
    private $availableFields;

    /**
     * @return array
     */
    public function getUniqueData(array $queryFields)
    {
        $uniqueLeadFields    = $this->fieldModel->getUniqueIdentifierFields(['object' => $this->object]);
        $uniqueLeadFieldData = [];
        $inQuery             = array_intersect_key($queryFields, $this->getAvailableFields());
        foreach ($inQuery as $k => $v) {
            // Don't use empty values when checking for duplicates
            if (empty($v)) {
                continue;
            }

            if (array_key_exists($k, $uniqueLeadFields)) {
                $uniqueLeadFieldData[$k] = $v;
            }
        }

        return $uniqueLeadFieldData;
    }

    /**
     * @return array
     */
    private function getAvailableFields()
    {
        if (null === $this->availableFields) {
            $this->availableFields = $this->fieldModel->getFieldList(
                false,
                false,
                [
                    'isPublished' => true,
                    'object'      => $this->object,
                ]
            );
        }

        return $this->availableFields;
    }
}
