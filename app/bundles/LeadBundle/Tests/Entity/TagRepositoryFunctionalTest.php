<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;

final class TagRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private TagRepository $tagRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagRepository = self::getContainer()->get(TagRepository::class);
    }

    public function testGetTagIdsByLeadId(): void
    {
        $tagOne    = $this->createTag('one');
        $tagTwo    = $this->createTag('two');
        $tagThree  = $this->createTag('three');
        $leadOne   = $this->createLead();
        $leadTwo   = $this->createLead();
        $leadThree = $this->createLead();
        $leadOne->addTag($tagOne);
        $leadOne->addTag($tagTwo);
        $leadTwo->addTag($tagThree);

        $this->em->flush();

        $this->assertEqualsCanonicalizing([$tagOne->getId(), $tagTwo->getId()], $this->tagRepository->getTagIdsByLeadId((string) $leadOne->getId()));
        $this->assertEqualsCanonicalizing([$tagThree->getId()], $this->tagRepository->getTagIdsByLeadId((string) $leadTwo->getId()));
        $this->assertEmpty($this->tagRepository->getTagIdsByLeadId((string) $leadThree->getId()));
        $this->assertEmpty($this->tagRepository->getTagIdsByLeadId('non-existing'));
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $this->em->persist($lead);

        return $lead;
    }

    private function createTag(string $name): Tag
    {
        $tag = new Tag();
        $tag->setTag($name);
        $this->em->persist($tag);

        return $tag;
    }
}
