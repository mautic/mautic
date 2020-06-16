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

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

class InformationChangeRequestDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var string
     */
    private $objectName;

    /**
     * @var mixed
     */
    private $objectId;

    /**
     * @var string
     */
    private $field;

    /**
     * @var NormalizedValueDAO
     */
    private $newValue;

    /**
     * @var \DateTimeInterface|null
     */
    private $possibleChangeDateTime;

    /**
     * @var \DateTimeInterface|null
     */
    private $certainChangeDateTime;

    /**
     * @param string $integration
     * @param string $objectName
     * @param mixed  $objectId
     * @param string $field
     */
    public function __construct($integration, $objectName, $objectId, $field, NormalizedValueDAO $normalizedValueDAO)
    {
        $this->integration = $integration;
        $this->objectName  = $objectName;
        $this->objectId    = $objectId;
        $this->field       = $field;
        $this->newValue    = $normalizedValueDAO;
    }

    public function getIntegration(): string
    {
        return $this->integration;
    }

    /**
     * @return mixed
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    public function getObject(): string
    {
        return $this->objectName;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getNewValue(): NormalizedValueDAO
    {
        return $this->newValue;
    }

    public function getPossibleChangeDateTime(): ?\DateTimeInterface
    {
        return $this->possibleChangeDateTime;
    }

    /**
     * @return InformationChangeRequestDAO
     */
    public function setPossibleChangeDateTime(?\DateTimeInterface $possibleChangeDateTime = null): self
    {
        $this->possibleChangeDateTime = $possibleChangeDateTime;

        return $this;
    }

    public function getCertainChangeDateTime(): ?\DateTimeInterface
    {
        return $this->certainChangeDateTime;
    }

    /**
     * @return InformationChangeRequestDAO
     */
    public function setCertainChangeDateTime(?\DateTimeInterface $certainChangeDateTime = null): self
    {
        $this->certainChangeDateTime = $certainChangeDateTime;

        return $this;
    }
}
