<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;

class ObjectMappingsDAO
{
    private $updatedMappings = [];

    private $newMappings = [];

    public function addUpdatedObjectMapping(ObjectMapping $objectMapping): void
    {
        $this->updatedMappings[] = $objectMapping;
    }

    public function addNewObjectMapping(ObjectMapping $objectMapping): void
    {
        $this->newMappings[] = $objectMapping;
    }

    public function getUpdatedMappings(): array
    {
        return $this->updatedMappings;
    }

    public function getNewMappings(): array
    {
        return $this->newMappings;
    }
}
