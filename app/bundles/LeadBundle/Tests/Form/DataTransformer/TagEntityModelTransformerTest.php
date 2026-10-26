<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Form\DataTransformer\TagEntityModelTransformer;
use PHPUnit\Framework\TestCase;

final class TagEntityModelTransformerTest extends TestCase
{
    public function testReverseTransformAcceptsArrayCollection(): void
    {
        $transformer = new TagEntityModelTransformer(
            $this->createStub(EntityManagerInterface::class),
            Tag::class,
            true
        );

        $collection = new ArrayCollection([
            new Tag('first-tag'),
            new Tag('second-tag'),
        ]);

        $this->assertSame(
            ['first-tag', 'second-tag'],
            $transformer->reverseTransform($collection)
        );
    }

    public function testReverseTransformReturnsEmptyArrayForNull(): void
    {
        $transformer = new TagEntityModelTransformer(
            $this->createStub(EntityManagerInterface::class),
            Tag::class,
            true
        );

        $this->assertSame([], $transformer->reverseTransform(null));
    }
}
