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
     * @var int
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
     * @return string|null
     */
    public function getObjectName(): ?string
    {
        return $this->objectName;
    }


    /**
     * @return string|null
     */
    public function getRelObjectName(): ?string
    {
        return $this->relObjectName;
    }


    /**
     * @return string|null
     */
    public function getRelFieldName(): ?string
    {
        return $this->relFieldName;
    }


    /**
     * @return string|null
     */
    public function getObjectIntegrationId(): ?string
    {
        return $this->objectIntegrationId;
    }

    /**
     * @return string|null
     */
    public function getRelObjectIntegrationId(): ?string
    {
        return $this->relObjectIntegrationId;
    }

    /**
     * @return int|null
     */
    public function getRelObjectInternalId(): ?int
    {
        return $this->relObjectInternalId;
    }

    /**
     * @param int $relObjectInternalId
     */
    public function setRelObjectInternalId(int $relObjectInternalId)
    {
        $this->relObjectInternalId = $relObjectInternalId;
    }
}