<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration\Dynamics\DataTransformer;

use MauticPlugin\MauticCrmBundle\Integration\Dynamics\DataTransformer\DTO\FormattedValueDTO;
use MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration;

/**
 * Transform data for Dynamics payload.
 *
 * Lookup data are sent in format["endidityid@odata.bind"] = "/entities(DFE54660-37CD-E511-80DE-6C3BE5A831DC)"
 *
 * Reset lookup value is able to just by API call
 * https://stackoverflow.com/questions/49329838/setting-null-for-single-valued-navigation-property-using-xrm-webapi
 */
class DynamicsDataTransformer
{
    /**
     * @var FormattedValueDTO[]
     */
    private $dataObjects = [];

    /**
     * @var DynamicsIntegration
     */
    private $dynamicsIntegration;

    public function __construct(DynamicsIntegration $dynamicsIntegration)
    {
        $this->dynamicsIntegration = $dynamicsIntegration;
    }

    public function getData(string $object, array $data): array
    {
        $this->parseData($object, $data);

        $data = [];
        foreach ($this->dataObjects as $formattedValueDTO) {
            if ($formattedValueDTO->isFieldToPayload()) {
                $data[$formattedValueDTO->getKeyForPaload()] = $formattedValueDTO->getValueForPayload();
            }
        }

        return $data;
    }

    public function getLookupReferencesToRemove(): array
    {
        $data = [];
        foreach ($this->dataObjects as $key => $formattedValueDTO) {
            if ($formattedValueDTO->isLookupType() && !$formattedValueDTO->isFieldToPayload()) {
                $data[] = $key;
            }
        }

        return $data;
    }

    private function parseData(string $object, array $data): void
    {
        $this->dataObjects = [];

        $fields = $this->dynamicsIntegration->getAvailableLeadFields();
        if (is_array($fields)) {
            foreach ($data as $key=> $value) {
                $this->dataObjects[$key] = new FormattedValueDTO($key, $value, $fields[$object][$key] ?? []);
            }
        }
    }
}
