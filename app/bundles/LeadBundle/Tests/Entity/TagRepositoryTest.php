<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;

class TagRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testSaveEntityWithUniqueNewTag()
    {
        $entity = new Tag('sometag');

        $mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['persist', 'flush'])
            ->getMock();

        $mockEntityManager->expects($this->once())
            ->method('persist')
            ->with($entity);

        $mockRepository = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager', 'findOneBy'])
            ->getMock();

        $mockRepository->method('getEntityManager')
            ->willReturn($mockEntityManager);

        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['tag' => 'sometag'])
            ->willReturn(null);

        $mockRepository->saveEntity($entity);

        $this->assertNull($entity->getId());
    }

    public function testSaveEntityWithExistingTag()
    {
        $entity = new Tag('sometag');
        $entity->setId(45);

        $mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['persist', 'flush'])
            ->getMock();

        $mockEntityManager->expects($this->once())
            ->method('persist')
            ->with($entity);

        $mockRepository = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager', 'findOneBy'])
            ->getMock();

        $mockRepository->method('getEntityManager')
            ->willReturn($mockEntityManager);

        $mockRepository->expects($this->never())
            ->method('findOneBy');

        $mockRepository->saveEntity($entity);

        $this->assertSame(45, $entity->getId());
    }

    public function testSaveEntityWithNewDuplicatedTag()
    {
        $entity = new Tag('sometag');

        $mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['persist', 'flush'])
            ->getMock();

        $mockEntityManager->expects($this->never())
            ->method('persist');

        $mockRepository = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager', 'findOneBy'])
            ->getMock();

        $mockRepository->method('getEntityManager')
            ->willReturn($mockEntityManager);

        $existingTag = new Tag('sometag');
        $existingTag->setId(23);

        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['tag' => 'sometag'])
            ->willReturn($existingTag);

        $mockRepository->saveEntity($entity);

        $this->assertSame(23, $entity->getId());
    }

    public function testRemoveMinusFromTags()
    {
        $mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = new TagRepository($mockEntityManager, $mockMetadata);

        $tags = [
            'sometag1',
            '-sometag2',
            'sometag3',
            '-sometag4',
        ];

        $expected = [
            'sometag1',
            'sometag2',
            'sometag3',
            'sometag4',
        ];

        $this->assertSame($expected, $repository->removeMinusFromTags($tags));
    }
}
