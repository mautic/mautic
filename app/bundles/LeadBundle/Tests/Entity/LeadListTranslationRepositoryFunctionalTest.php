<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListTranslation;
use Mautic\LeadBundle\Entity\LeadListTranslationRepository;

final class LeadListTranslationRepositoryFunctionalTest extends MauticMysqlTestCase
{
    public function testFindsTranslationsForRequestedSegmentsOnly(): void
    {
        $first  = $this->createSegment('First');
        $second = $this->createSegment('Second');
        $other  = $this->createSegment('Other');

        $this->em->persist(new LeadListTranslation($first, 'fr', 'Actualités Mautic'));
        $this->em->persist(new LeadListTranslation($first, 'en', 'Mautic News'));
        $this->em->persist(new LeadListTranslation($second, 'fr_CH', 'Nouvelles Mautic'));
        $this->em->persist(new LeadListTranslation($other, 'it', 'Notizie Mautic'));
        $this->em->flush();
        $this->em->clear();

        $repository = self::getContainer()->get(LeadListTranslationRepository::class);
        $this->assertInstanceOf(LeadListTranslationRepository::class, $repository);

        $this->assertSame([
            $first->getId() => ['en' => 'Mautic News', 'fr' => 'Actualités Mautic'],
            $second->getId() => ['fr_CH' => 'Nouvelles Mautic'],
        ], $repository->findForSegmentIds([$first->getId(), $second->getId()]));
    }

    public function testEmptySegmentIdsReturnNoTranslations(): void
    {
        $repository = self::getContainer()->get(LeadListTranslationRepository::class);
        $this->assertInstanceOf(LeadListTranslationRepository::class, $repository);

        $this->assertSame([], $repository->findForSegmentIds([]));
    }

    private function createSegment(string $name): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias('translation-'.$name);
        $this->em->persist($segment);

        return $segment;
    }
}
