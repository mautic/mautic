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

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

class FieldMappingDAO
{
    /**
     * @var string
     */
    private $internalObject;

    /**
     * @var string
     */
    private $internalField;

    /**
     * @var string
     */
    private $integrationObject;

    /**
     * @var string
     */
    private $integrationField;

    /**
     * @var string
     */
    private $syncDirection;

    /**
     * @var bool
     */
    private $isRequired;

    /**
     * FieldMappingDAO constructor.
     *
     * @param string $internalObject
     * @param string $internalField
     * @param string $integrationObject
     * @param string $integrationField
     * @param string $syncDirection
     * @param bool   $isRequired
     */
    public function __construct($internalObject, $internalField, $integrationObject, $integrationField, $syncDirection, $isRequired)
    {
        $this->internalObject    = $internalObject;
        $this->internalField     = $internalField;
        $this->integrationObject = $integrationObject;
        $this->integrationField  = $integrationField;
        $this->syncDirection     = $syncDirection;
        $this->isRequired        = (bool) $isRequired;
    }

    /**
     * @return string
     */
    public function getInternalObject()
    {
        return $this->internalObject;
    }

    /**
     * @return string
     */
    public function getInternalField()
    {
        return $this->internalField;
    }

    /**
     * @return string
     */
    public function getIntegrationObject()
    {
        return $this->integrationObject;
    }

    /**
     * @return string
     */
    public function getIntegrationField()
    {
        return $this->integrationField;
    }

    /**
     * @return string
     */
    public function getSyncDirection()
    {
        return $this->syncDirection;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }
}
