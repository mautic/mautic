<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentSubscriberTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['delete_segment_in_background'] = false;
        parent::setUp();

        $this->saveContacts();
    }

    /**
     * @param mixed[]  $filters
     * @param string[] $expectedTranslations
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('filterProvider')]
    public function testSegmentFilterAlertMessages(array $filters, array $expectedTranslations): void
    {
        $segment   = $this->saveSegment('Segment D', 'segment-d', $filters);
        $crawler   = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$segment->getId());
        Assert::assertTrue($this->client->getResponse()->isOk());
        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        $expectedTranslationString = implode(' ', array_map(fn ($trans) => $translator->trans($trans), $expectedTranslations));

        $crawlerText = $crawler->filter('#leadlist_filters_0_properties')->filter('.alert')->text();
        $this->assertStringContainsString($expectedTranslationString, $crawlerText);
    }

    /**
     * @return \Generator<array<mixed>>
     */
    public static function filterProvider(): \Generator
    {
        yield [[
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'operator' => 'like',
            ],
        ], ['mautic.lead_list.filter.alert.like', 'mautic.lead_list.filter.alert.email']];
        yield [[
            [
                'glue'     => 'and',
                'field'    => 'firstname',
                'object'   => 'lead',
                'type'     => 'text',
                'operator' => 'contains',
            ],
        ], ['mautic.lead_list.filter.alert.contain']];
        yield [[
            [
                'glue'     => 'and',
                'field'    => 'firstname',
                'object'   => 'lead',
                'type'     => 'text',
                'operator' => 'like',
            ],
        ], ['mautic.lead_list.filter.alert.like']];
        yield [[
            [
                'glue'     => 'and',
                'field'    => 'firstname',
                'object'   => 'lead',
                'type'     => 'text',
                'operator' => 'endsWith',
            ],
        ], ['mautic.lead_list.filter.alert.endwith']];
    }

    public function testSegmentDeleteWhenBackgroundConfigFalse(): void
    {
        $filters = [
            [
                'glue'       => 'and',
                'field'      => 'firstname',
                'object'     => 'lead',
                'type'       => 'text',
                'operator'   => 'like',
                'properties' => ['filter' => 'Contact'],
            ],
        ];
        $segment   = $this->saveSegment('SegmentD', 'segment-d', $filters);
        $segmentId = $segment->getId();

        // Run segments update command.
        $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segmentId]);

        $listModel = $this->getContainer()->get('mautic.lead.model.list');
        \assert($listModel instanceof ListModel);

        $leadCount = $listModel->getListLeadRepository()->getContactsCountBySegment($segmentId);
        self::assertSame(5, $leadCount);

        $listModel->deleteEntity($segment);
        $this->em->flush();

        self::assertNull($listModel->getEntity($segmentId));

        $deletedEntity = $listModel->getSoftDeletedEntity($segmentId);
        self::assertNull($deletedEntity);

        $leadCount = $listModel->getListLeadRepository()->getContactsCountBySegment($segmentId);
        self::assertSame(0, $leadCount);
    }

    /**
     * @return array<mixed>
     */
    private function saveContacts(): array
    {
        // Add 5 contacts
        $contactRepo = $this->em->getRepository(Lead::class);
        \assert($contactRepo instanceof LeadRepository);

        $contacts = [];

        for ($i = 1; $i <= 5; ++$i) {
            $contact = new Lead();
            $contact->setFirstname('Contact '.$i);
            $contacts[] = $contact;
        }

        $contactRepo->saveEntities($contacts);

        return $contacts;
    }

    /**
     * @param array<mixed> $filters
     */
    private function saveSegment(string $name, string $alias, array $filters): LeadList
    {
        $segmentRepo = $this->em->getRepository(LeadList::class);
        \assert($segmentRepo instanceof LeadListRepository);
        $segment     = new LeadList();
        $segment->setName($name)
            ->setPublicName($name)
            ->setFilters($filters)
            ->setAlias($alias);
        $segmentRepo->saveEntity($segment);

        return $segment;
    }
}
