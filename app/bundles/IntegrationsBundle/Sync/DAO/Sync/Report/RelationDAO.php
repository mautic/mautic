<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Report;

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

    public function getObjectName(): string
    {
        return $this->objectName;
    }

    public function getRelObjectName(): string
    {
        return $this->relObjectName;
    }

    public function getRelFieldName(): string
    {
        return $this->relFieldName;
    }

    public function getObjectIntegrationId(): string
    {
        return $this->objectIntegrationId;
    }

    public function getRelObjectIntegrationId(): string
    {
        return $this->relObjectIntegrationId;
    }

    public function getRelObjectInternalId(): ?int
    {
        return $this->relObjectInternalId;
    }

    public function setRelObjectInternalId(int $relObjectInternalId): void
    {
        $this->relObjectInternalId = $relObjectInternalId;
    }
}
