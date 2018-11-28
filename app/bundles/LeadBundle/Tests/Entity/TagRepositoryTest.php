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
    public function testGetTagByNameOrCreateNewOneWithSomeExistingTag()
    {
        $fetchedEntity = new Tag('sometag');

        $mockRepository = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['tag' => 'sometag'])
            ->willReturn($fetchedEntity);

        $this->assertSame($fetchedEntity, $mockRepository->getTagByNameOrCreateNewOne('sometag'));
    }

    public function testGetTagByNameOrCreateNewOneWithSomeNewTag()
    {
        $mockRepository = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['tag' => 'sometag'])
            ->willReturn(null);

        $newEntity = $mockRepository->getTagByNameOrCreateNewOne('sometag');

        $this->assertSame('sometag', $newEntity->getTag());
        $this->assertNull($newEntity->getId());
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
