<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Services;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListTranslationRepository;
use Mautic\LeadBundle\Services\SegmentPublicNameTranslationManager;

final class SegmentPublicNameTranslationManagerFunctionalTest extends MauticMysqlTestCase
{
    public function testCreatesUpdatesAndRemovesTranslationsWithoutFlushing(): void
    {
        $segment = $this->createSegment('News');
        $manager = $this->getManager();

        $manager->sync($segment, [
            ['locale' => 'fr-CH', 'publicName' => 'Actualités Mautic'],
            ['locale' => 'en', 'publicName' => 'Mautic News'],
        ]);

        $this->assertSame([], $this->getTranslations($segment));
        $this->em->flush();
        $this->assertSame(['en' => 'Mautic News', 'fr_CH' => 'Actualités Mautic'], $this->getTranslations($segment));

        $manager->sync($segment, [
            ['locale' => 'fr_CH', 'publicName' => 'Nouvelles Mautic'],
        ]);
        $this->em->flush();

        $this->assertSame(['fr_CH' => 'Nouvelles Mautic'], $this->getTranslations($segment));
    }

    public function testRejectsDuplicateNormalizedLocalesBeforeWriting(): void
    {
        $segment = $this->createSegment('Duplicates');

        $this->expectException(\InvalidArgumentException::class);
        $this->getManager()->sync($segment, [
            ['locale' => 'fr-CH', 'publicName' => 'Actualités'],
            ['locale' => 'fr_CH', 'publicName' => 'Nouvelles'],
        ]);
    }

    public function testRejectsBlankPublicNames(): void
    {
        $segment = $this->createSegment('Blank');

        $this->expectException(\InvalidArgumentException::class);
        $this->getManager()->sync($segment, [['locale' => 'fr', 'publicName' => '  ']]);
    }

    public function testNonPublicSegmentCannotChangeStoredTranslations(): void
    {
        $segment = $this->createSegment('Private');
        $this->getManager()->sync($segment, [['locale' => 'fr', 'publicName' => 'Actualités']]);
        $this->em->flush();

        $segment->setIsPreferenceCenter(false);
        $this->expectException(\LogicException::class);
        try {
            $this->getManager()->sync($segment, []);
        } finally {
            $this->assertSame(['fr' => 'Actualités'], $this->getTranslations($segment));
        }
    }

    private function createSegment(string $name): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias('manager-'.$name);
        $segment->setIsPreferenceCenter(true);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function getManager(): SegmentPublicNameTranslationManager
    {
        $manager = self::getContainer()->get(SegmentPublicNameTranslationManager::class);
        $this->assertInstanceOf(SegmentPublicNameTranslationManager::class, $manager);

        return $manager;
    }

    /**
     * @return array<string, string>
     */
    private function getTranslations(LeadList $segment): array
    {
        $repository = self::getContainer()->get(LeadListTranslationRepository::class);
        $this->assertInstanceOf(LeadListTranslationRepository::class, $repository);

        return $repository->findForSegmentIds([$segment->getId()])[$segment->getId()] ?? [];
    }
}
