<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectMappingsDAO;
use PHPUnit\Framework\TestCase;

final class ObjectMappingsDAOTest extends TestCase
{
    public function testGetters(): void
    {
        $objectMappings = new ObjectMappingsDAO();

        $objectMappings->addNewObjectMapping((new ObjectMapping())->setIntegrationObjectName('foonew'));
        $objectMappings->addNewObjectMapping((new ObjectMapping())->setIntegrationObjectName('barnew'));
        $mappings = $objectMappings->getNewMappings();
        $this->assertCount(2, $mappings);
        $this->assertEquals('foonew', $mappings[0]->getIntegrationObjectName());
        $this->assertEquals('barnew', $mappings[1]->getIntegrationObjectName());

        $objectMappings->addUpdatedObjectMapping((new ObjectMapping())->setIntegrationObjectName('fooupdate'));
        $objectMappings->addUpdatedObjectMapping((new ObjectMapping())->setIntegrationObjectName('barupdate'));
        $mappings = $objectMappings->getUpdatedMappings();
        $this->assertCount(2, $mappings);
        $this->assertEquals('fooupdate', $mappings[0]->getIntegrationObjectName());
        $this->assertEquals('barupdate', $mappings[1]->getIntegrationObjectName());
    }
}
