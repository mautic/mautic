<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Tests;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\LeadBundle\Entity\Lead;


class EntityResultHelperTest extends \PHPUnit_Framework_TestCase
{
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

        $arrayResult = $resultHelper->getArray($results, function($entity) {
            $this->preserializeCallbackTest($entity);
        });

        foreach($arrayResult as $entity) {
            $this->assertEquals($entity->getTitle(), 'Callback Title');
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

        $arrayResult = $resultHelper->getArray($results, function($entity) {
            $this->preserializeCallbackTest($entity);
        });

        foreach($arrayResult as $entity) {
            $this->assertEquals($entity->getTitle(), 'Callback Title');
        }
    }

    public function testGetArrayAppendedData()
    {
        $resultHelper = new EntityResultHelper();

        $lead2 = new Lead();
        $lead2->setId(2);

        $lead5 = new Lead();
        $lead5->setId(5);

        $data = [[$lead2, 'title' => 'Title 2'], [$lead5, 'title' => 'Title 5']];

        $expectedResult = [$lead2, $lead5];;

        $arrayResult = $resultHelper->getArray($data);

        $this->assertEquals($expectedResult, $arrayResult);

        foreach($arrayResult as $entity) {
            $this->assertEquals($entity->getTitle(), 'Title '.$entity->getId());
        }

        $arrayResult = $resultHelper->getArray($data, function($entity) {
            $this->preserializeCallbackTest($entity);
        });

        foreach($arrayResult as $entity) {
            $this->assertEquals($entity->getTitle(), 'Callback Title');
        }
    }

    private function preserializeCallbackTest(Lead $entity)
    {
        $entity->setTitle('Callback Title');
    }
}
