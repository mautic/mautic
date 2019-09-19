<?php
/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report;

class RelationDAO
{
    /**
     * @var string
     */
    private $objectName;

    /**
     * @var string
     */
    private $relFieldName;

    /**
     * @var string
     */
    private $relObjectName;

    /**
     * @var string
     */
    private $objectIntegrationId;

    /**
     * @var string
     */
    private $relObjectIntegrationId;

    /**
     * @var string
     */
    private $relObjectInternalId;


    public function __construct(string $objectName, string $relFieldName, string $relObjectName, string $objectIntegrationId, string $relObjectIntegrationId)
    {
        $this->objectName             = $objectName;
        $this->relFieldName           = $relFieldName;
        $this->relObjectName          = $relObjectName;
        $this->objectIntegrationId    = $objectIntegrationId;
        $this->relObjectIntegrationId = $relObjectIntegrationId;
    }

    /**
     * @return string
     */
    public function getObjectName()
    {
        return $this->objectName;
    }

    /**
     * @return string
     */
    public function getRelObjectName()
    {
        return $this->relObjectName;
    }

    /**
     * @return string
     */
    public function getRelFieldName()
    {
        return $this->relFieldName;
    }

    /**
     * @return string
     */
    public function getObjectIntegrationId()
    {
        return $this->objectIntegrationId;
    }

    /**
     * @return string
     */
    public function getRelObjectIntegrationId()
    {
        return $this->relObjectIntegrationId;
    }

    /**
     * @return string
     */
    public function getRelObjectInternalId()
    {
        return $this->relObjectInternalId;
    }

    /**
     * @param string $relObjectInternalId
     */
    public function setRelObjectInternalId($relObjectInternalId)
    {
        $this->relObjectInternalId = $relObjectInternalId;
    }
}