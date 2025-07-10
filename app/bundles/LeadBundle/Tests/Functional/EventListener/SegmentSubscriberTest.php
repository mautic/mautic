<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentSubscriberTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider filterProvider
     *
     * @param mixed[]  $filters
     * @param string[] $expectedTranslations
     */
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
    public function filterProvider(): \Generator
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
