<?php

namespace Mautic\LeadBundle\Repository\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\LeadBundle\Entity\LeadListRepository;

class LeadListRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTypeNameFromValue()
    {
        $repo     = $this->initRepo();
        $testData = [
            [
                'val' => 'string',
                'res' => null,
            ],
            [
                'val' => '324',
                'res' => null,
            ],
            [
                'val' => '324.87',
                'res' => null,
            ],
            [
                'val' => null,
                'res' => null,
            ],
            [
                'val' => false,
                'res' => 'boolean',
            ],
            [
                'val' => 45,
                'res' => 'integer',
            ],
            [
                'val' => 0,
                'res' => 'integer',
            ],
            [
                'val' => 2323.343,
                'res' => 'float',
            ],
            [
                'val' => -453.00,
                'res' => 'float',
            ],
        ];

        foreach ($testData as $td) {
            $res = $repo->getTypeNameFromValue($td['val']);
            $this->assertSame($td['res'], $res, 'test failed for row '.print_r($td, true));
        }
    }

    protected function initRepo()
    {
        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $classMetadata = $this
            ->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new LeadListRepository($entityManager, $classMetadata);
    }
}
