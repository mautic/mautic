<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping;


use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;

class UpdatedObjectMappingDAO
{
    /**
     * @var ObjectChangeDAO
     */
    private $objectChangeDAO;

    /**
     * @var mixed
     */
    private $objectId;

    /**
     * @var string
     */
    private $objectName;

    /**
     * @var \DateTime
     */
    private $objectModifiedDate;

    /**
     * UpdatedObjectMappingDAO constructor.
     *
     * @param ObjectChangeDAO $objectChangeDAO
     * @param  mixed          $objectId
     * @param       string    $objectName
     * @param \DateTime       $objectModifiedDate
     */
    public function __construct(ObjectChangeDAO $objectChangeDAO, $objectId, $objectName, \DateTime $objectModifiedDate)
    {
        $this->objectChangeDAO    = $objectChangeDAO;
        $this->objectId           = $objectId;
        $this->objectName         = $objectName;
        $this->objectModifiedDate = $objectModifiedDate;
    }

    /**
     * @return ObjectChangeDAO
     */
    public function getObjectChangeDAO(): ObjectChangeDAO
    {
        return $this->objectChangeDAO;
    }

    /**
     * @return mixed
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @return string
     */
    public function getObjectName(): string
    {
        return $this->objectName;
    }

    /**
     * @return \DateTime
     */
    public function getObjectModifiedDate(): \DateTime
    {
        return $this->objectModifiedDate;
    }
}