<?php

namespace MauticPlugin\MauticTagManagerBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Tag;
use MauticPlugin\MauticTagManagerBundle\Entity\TagRepository;
use PHPUnit\Framework\Assert;

class TagRepositoryTest extends MauticMysqlTestCase
{
    private TagRepository $tagRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagRepository = self::getContainer()->get('mautic.tagmanager.repository.tag');

        $tags = [
            'tag1',
            'tag2',
            'tag3',
            'tag4',
        ];

        foreach ($tags as $tagName) {
            $tag = new Tag();
            $tag->setTag($tagName);
            $this->tagRepository->saveEntity($tag);
        }
    }

    public function testCountOccurencesReturnsCorrectQuantityOfTags(): void
    {
        $count = $this->tagRepository->countOccurrences('tag2');
        Assert::assertSame(1, $count);
    }
}
