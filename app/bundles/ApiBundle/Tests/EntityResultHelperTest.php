<?php

namespace Mautic\ApiBundle\Tests;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\TestCase;

class EntityResultHelperTest extends TestCase
{
    const NEW_TITLE = 'Callback Title';

    public function testGetArrayEntities()
    {
        $resultHelper = new EntityResultHelper();

        $lead2 = new Lead();
        $lead2->setId(2);

        $lead5 = new Lead();
        $lead5->setId(5);

        $results = [2 => $lead2, 5 => $lead5];

        $arrayResult = $resultHelper->getArray($results);

        $this->assertEquals($results, $arrayResult);

        $arrayResult = $resultHelper->getArray($results, function ($entity) {
            $this->modifyEntityData($entity);
        });

        foreach ($arrayResult as $entity) {
            $this->assertEquals($entity->getTitle(), self::NEW_TITLE);
        }
    }

    public function testGetArrayPaginator()
    {
        $resultHelper = new EntityResultHelper();

        $lead2 = new Lead();
        $lead2->setId(2);

        $lead5 = new Lead();
        $lead5->setId(5);

        $results = [$lead2, $lead5];

        $iterator = new \ArrayIterator($results);

        $paginator = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIterator'])
            ->getMock();

        $paginator->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue($iterator));

        $arrayResult = $resultHelper->getArray($paginator);

        $this->assertEquals($results, $arrayResult);

        $arrayResult = $resultHelper->getArray($results, function ($entity) {
            $this->modifyEntityData($entity);
        });

        foreach ($arrayResult as $entity) {
            $this->assertEquals($entity->getTitle(), self::NEW_TITLE);
        }
    }

    public function testGetArrayAppendedData()
    {
        $resultHelper = new EntityResultHelper();

        $lead2 = new Lead();
        $lead2->setId(2);

        $lead5 = new Lead();
        $lead5->setId(5);

        $lead7 = new Lead();
        $lead7->setId(7);

        $data = [[$lead2, 'title' => 'Title 2'], [$lead5, 'title' => 'Title 5'], [$lead7, 'title' => 'Title 7']];

        $expectedResult = [$lead2, $lead5, $lead7];

        $arrayResult = $resultHelper->getArray($data);

        $this->assertEquals($expectedResult, $arrayResult);

        foreach ($arrayResult as $entity) {
            $this->assertEquals($entity->getTitle(), 'Title '.$entity->getId());
        }

        $arrayResult = $resultHelper->getArray($data, function ($entity) {
            $this->modifyEntityData($entity);
        });

        foreach ($arrayResult as $entity) {
            $this->assertEquals($entity->getTitle(), self::NEW_TITLE);
        }
    }

    private function modifyEntityData(Lead $entity)
    {
        $entity->setTitle(self::NEW_TITLE);
    }
}
