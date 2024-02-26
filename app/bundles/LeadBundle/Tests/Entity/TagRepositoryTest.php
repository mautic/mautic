<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;

class TagRepositoryTest extends \PHPUnit\Framework\TestCase
{
    use RepositoryConfiguratorTrait;

    public function testGetTagByNameOrCreateNewOneWithSomeExistingTag(): void
    {
        $fetchedEntity = new Tag('sometag');

        $mockRepository = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['tag' => 'sometag'])
            ->willReturn($fetchedEntity);

        $this->assertSame($fetchedEntity, $mockRepository->getTagByNameOrCreateNewOne('sometag'));
    }

    public function testGetTagByNameOrCreateNewOneWithSomeNewTag(): void
    {
        $mockRepository = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['tag' => 'sometag'])
            ->willReturn(null);

        $newEntity = $mockRepository->getTagByNameOrCreateNewOne('sometag');

        $this->assertSame('sometag', $newEntity->getTag());
        $this->assertNull($newEntity->getId());
    }

    public function testGetTagByNameOrCreateNewOneInputFilter(): void
    {
        $fetchedEntity = new Tag('hello" world');

        $mockRepository = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['tag' => 'hello" world'])
            ->willReturn($fetchedEntity);

        $this->assertSame($fetchedEntity, $mockRepository->getTagByNameOrCreateNewOne('hello" world'));
    }

    public function testRemoveMinusFromTags(): void
    {
        $repository = $this->configureRepository(Tag::class);

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
