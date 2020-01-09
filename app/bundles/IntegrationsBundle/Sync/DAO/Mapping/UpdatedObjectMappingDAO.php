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

class UpdatedObjectMappingDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var string
     */
    private $integrationObjectName;

    /**
     * @var mixed
     */
    private $integrationObjectId;

    /**
     * @var \DateTime
     */
    private $objectModifiedDate;

    /**
     * @param string $integration
     * @param string $integrationObjectName
     * @param mixed  $integrationObjectId
     */
    public function __construct(
        $integration,
        $integrationObjectName,
        $integrationObjectId,
        \DateTimeInterface $objectModifiedDate
    ) {
        $this->integration           = $integration;
        $this->integrationObjectName = $integrationObjectName;
        $this->integrationObjectId   = $integrationObjectId;
        $this->objectModifiedDate    = $objectModifiedDate instanceof \DateTimeImmutable ? new \DateTime(
            $objectModifiedDate->format('Y-m-d H:i:s'),
            $objectModifiedDate->getTimezone()
        ) : $objectModifiedDate;
    }

    public function getIntegration(): string
    {
        return $this->integration;
    }

    public function getIntegrationObjectName(): string
    {
        return $this->integrationObjectName;
    }

    /**
     * @return mixed
     */
    public function getIntegrationObjectId()
    {
        return $this->integrationObjectId;
    }

    public function getObjectModifiedDate(): \DateTimeInterface
    {
        return $this->objectModifiedDate;
    }
}
