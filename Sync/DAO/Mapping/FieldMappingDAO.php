<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping;

/**
 * Class FieldMappingDAO
 */
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
     * FieldMappingDAO constructor.
     *
     * @param $internalObject
     * @param $internalField
     * @param $integrationObject
     * @param $integrationField
     * @param $syncDirection
     */
    public function __construct($internalObject, $internalField, $integrationObject, $integrationField, $syncDirection)
    {
        $this->internalObject    = $internalObject;
        $this->internalField     = $internalField;
        $this->integrationObject = $integrationObject;
        $this->integrationField  = $integrationField;
        $this->syncDirection     = $syncDirection;
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
}
