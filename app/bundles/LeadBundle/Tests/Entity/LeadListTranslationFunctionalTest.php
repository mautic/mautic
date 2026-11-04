<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListTranslation;

final class LeadListTranslationFunctionalTest extends MauticMysqlTestCase
{
    public function testTranslationBelongsToOneSegmentAndIsRemovedWithIt(): void
    {
        $segment = new LeadList();
        $segment->setName('News');
        $segment->setPublicName('News');
        $segment->setAlias('news-translations');

        $translation = new LeadListTranslation($segment, 'fr_CH', 'Actualités');

        $this->em->persist($segment);
        $this->em->persist($translation);
        $this->em->flush();

        $segmentId     = $segment->getId();
        $translationId = $translation->getId();
        $this->em->clear();

        $stored = $this->em->find(LeadListTranslation::class, $translationId);
        self::assertInstanceOf(LeadListTranslation::class, $stored);
        self::assertSame('fr_CH', $stored->getLocale());
        self::assertSame('Actualités', $stored->getPublicName());
        self::assertSame($segmentId, $stored->getLeadList()->getId());

        $this->em->remove($stored->getLeadList());
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->find(LeadListTranslation::class, $translationId));
    }

    public function testLocaleMustBeUniqueWithinEachSegment(): void
    {
        $segment = new LeadList();
        $segment->setName('News');
        $segment->setPublicName('News');
        $segment->setAlias('news-unique-locale');
        $this->em->persist($segment);
        $this->em->persist(new LeadListTranslation($segment, 'fr', 'Actualités'));
        $this->em->flush();

        $table = $this->em->getClassMetadata(LeadListTranslation::class)->getTableName();
        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->getConnection()->insert($table, [
            'leadlist_id' => $segment->getId(),
            'locale'      => 'fr',
            'public_name' => 'Nouvelles',
        ]);
    }
}
