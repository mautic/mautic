<?php

namespace MauticPlugin\MauticTagManagerBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Tag;
use MauticPlugin\MauticTagManagerBundle\Entity\TagRepository;
use MauticPlugin\MauticTagManagerBundle\Model\TagModel;
use PHPUnit\Framework\Assert;

class TagRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @var TagRepository
     */
    private $tagRepository;

    /**
     * @var TagModel
     */
    private $tagModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagRepository = self::$container->get('mautic.tagmanager.repository.tag');
        $this->tagModel      = self::$container->get('mautic.tagmanager.repository.tag');

        $tags = [
            'tag1',
            'tag2',
            'tag3',
            'tag4',
        ];

        foreach ($tags as $tagName) {
            $tag = new Tag();
            $tag->setTag($tagName);
            $this->tagModel->saveEntity($tag);
        }
    }

    public function testCountOccurencesReturnsCorrectQuantityOfTags(): void
    {
        $count = $this->tagRepository->countOccurrences('tag2');
        Assert::assertSame(1, $count);
    }
}
