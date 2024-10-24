<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var ListModel
     */
    protected $listModel;

    /**
     * @var LeadListRepository
     */
    protected $listRepo;

    /**
     * @var LeadRepository
     */
    protected $leadRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listModel = static::getContainer()->get('mautic.lead.model.list');
        \assert($this->listModel instanceof ListModel);
        $this->listRepo = $this->listModel->getRepository();
        \assert($this->listRepo instanceof LeadListRepository);
        /** @var LeadModel $leadModel */
        $leadModel      = static::getContainer()->get('mautic.lead.model.lead');
        $this->leadRepo = $leadModel->getRepository();
    }

    public function testUnpublishUsedSegment(): void
    {
        $filter = [[
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '!empty',
            'display'  => '',
        ]];
        $list1  = $this->saveSegment('s1', 's1', $filter);
        $filter = [[
            'object'     => 'lead',
            'glue'       => 'and',
            'field'      => 'leadlist',
            'type'       => 'leadlist',
            'operator'   => 'in',
            'properties' => [
                'filter' => [$list1->getId()],
            ],
            'display' => '',
        ]];
        $list2 = $this->saveSegment('s2', 's2', $filter);
        $this->em->clear();
        $expectedErrorMessage = sprintf('This segment is used in %s, please go back and check segments before unpublishing', $list2->getName());

        $crawler = $this->client->request(Request::METHOD_POST, '/s/ajax', ['action' => 'togglePublishStatus', 'model' => 'lead.list', 'id' => $list1->getId()]);
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString($expectedErrorMessage, $this->client->getResponse()->getContent());
        $this->client->restart();
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$list1->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[isPublished]']->setValue('0');
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString($expectedErrorMessage, $this->client->getResponse()->getContent());
    }

    public function testUnpublishUnUsedSegment(): void
    {
        $filter = [[
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '!empty',
            'display'  => '',
        ]];
        $list1 = $this->saveSegment('s1', 's1', $filter);
        $list2 = $this->saveSegment('s2', 's2', $filter);
        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_POST, '/s/ajax', ['action' => 'togglePublishStatus', 'model' => 'lead.list', 'id' => $list1->getId()]);
        $this->assertTrue($this->client->getResponse()->isOk());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$list2->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[isPublished]']->setValue('0');
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $rows = $this->listRepo->findAll();
        $this->assertCount(2, $rows);
        $this->assertFalse($rows[0]->isPublished());
        $this->assertFalse($rows[1]->isPublished());
    }

    public function testBCSegmentWithPageHitInLeadObject(): void
    {
        $segment = $this->saveSegment(
            'Legacy Url Hit segment',
            's1',
            [
                [
                    'glue'     => 'and',
                    'field'    => 'hit_url',
                    'object'   => 'lead',
                    'type'     => 'text',
                    'filter'   => 'unicorn',
                    'display'  => null,
                    'operator' => '=',
                ],
            ]
        );

        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$segment->getId());
        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertGreaterThan(0, $crawler->filter('#leadlist_filters_0_operator option')->count());
    }

    private function saveSegment(string $name, string $alias, array $filters = [], LeadList $segment = null): LeadList
    {
        $segment ??= new LeadList();
        $segment->setName($name)->setAlias($alias)->setFilters($filters);
        $this->listModel->saveEntity($segment);

        return $segment;
    }

    /**
     * @throws \Exception
     */
    public function testSegmentCount(): void
    {
        // Save segment.
        $filters   = [
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => null,
                'display'  => null,
                'operator' => '!empty',
            ],
        ];
        $segment   = $this->saveSegment('Lead List 1', 'lead-list-1', $filters);
        $segmentId = $segment->getId();

        // Check segment count UI for no contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        self::assertSame('No Contacts', $html);

        // Add 4 contacts.
        $contacts   = $this->saveContacts();
        $contact1Id = $contacts[0]->getId();

        // Rebuild segment - set current count to the cache.
        $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segmentId, '--env' => 'test']);

        // Check segment count UI for 4 contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        self::assertSame('View 4 Contacts', $html);

        // Remove 1 contact from segment.
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contact/'.$contact1Id.'/remove');
        self::assertSame('{"success":1}', $this->client->getResponse()->getContent());
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        // Check segment count UI for 3 contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        self::assertSame('View 3 Contacts', $html);

        // Add 1 contact back to segment.
        $parameters = ['ids' => [$contact1Id]];
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contacts/add', $parameters);
        self::assertSame('{"success":1,"details":{"'.$contact1Id.'":{"success":true}}}', $this->client->getResponse()->getContent());
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        // Check segment count UI for 4 contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        self::assertSame('View 4 Contacts', $html);

        // Check segment count AJAX for 4 contacts.
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        self::assertSame('View 4 Contacts', $response['content']['html']);
        self::assertSame(4, $response['content']['leadCount']);
        self::assertSame(Response::HTTP_OK, $response['statusCode']);

        // Remove 1 contact from segment.
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contact/'.$contact1Id.'/remove');
        self::assertSame('{"success":1}', $this->client->getResponse()->getContent());
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        // Check segment count AJAX for 3 contacts.
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        self::assertSame('View 3 Contacts', $response['content']['html']);
        self::assertSame(3, $response['content']['leadCount']);
        self::assertSame(Response::HTTP_OK, $response['statusCode']);

        // Add 1 contact back to segment.
        $parameters = ['ids' => [$contact1Id]];
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contacts/add', $parameters);
        self::assertSame('{"success":1,"details":{"'.$contact1Id.'":{"success":true}}}', $this->client->getResponse()->getContent());
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        // Check segment count AJAX for 4 contacts.
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        self::assertSame('View 4 Contacts', $response['content']['html']);
        self::assertSame(4, $response['content']['leadCount']);
        self::assertSame(Response::HTTP_OK, $response['statusCode']);
    }

    public function testSegmentNotFoundOnAjax(): void
    {
        // Emulate invalid request parameter.
        $parameter = ['id' => 'ABC'];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);

        self::assertSame('No Contacts', $response['content']['html']);
        self::assertSame(0, $response['content']['leadCount']);
        self::assertSame(Response::HTTP_NOT_FOUND, $response['statusCode']);
    }

    /**
     * @return Lead[]
     */
    private function saveContacts(int $count = 4): array
    {
        $contacts = [];

        for ($i = 1; $i <= $count; ++$i) {
            $contact = new Lead();
            $contact->setFirstname('Contact '.$i)->setEmail('contact'.$i.'@example.com');
            $contacts[] = $contact;
        }

        $this->leadRepo->saveEntities($contacts);

        return $contacts;
    }

    private function getSegmentCountHtml(Crawler $crawler, int $id): string
    {
        $content = $crawler->filter('a.col-count')->filter('a[data-id="'.$id.'"]')->html();

        return trim($content);
    }

    /**
     * @param array<string, mixed> $parameter
     *
     * @return array<string, mixed>
     */
    private function callGetLeadCountAjaxRequest(array $parameter): array
    {
        $this->client->request(Request::METHOD_POST, '/s/ajax?action=lead:getLeadCount', $parameter);
        $clientResponse = $this->client->getResponse();

        return [
            'content'    => json_decode($clientResponse->getContent(), true),
            'statusCode' => $this->client->getResponse()->getStatusCode(),
        ];
    }

    public function testCloneSegment(): void
    {
        $segment = $this->saveSegment(
            'Clone Segment',
            'clonesegment',
        );

        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_POST, '/s/segments/clone/'.$segment->getId());
        $this->assertTrue($this->client->getResponse()->isOk());

        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[alias]']->setValue('clonesegment2');
        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isOk());

        $this->client->submit($form);

        $rows = $this->listRepo->findAll();
        $this->assertCount(2, $rows);

        $this->assertSame('clonesegment', $rows[0]->getAlias());
        $this->assertSame('clonesegment2', $rows[1]->getAlias());
    }

    public function testSegmentFilterIcon(): void
    {
        // Save segment.
        $filters   = [
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => null,
                'display'  => null,
                'operator' => '!empty',
            ],
        ];
        $this->saveSegment('Lead List 1', 'lead-list-1', $filters);
        $this->saveSegment('Lead List 2', 'lead-list-2');

        // Check segment count UI for no contacts.
        $crawler            = $this->client->request(Request::METHOD_GET, '/s/segments');
        $leadListsTableRows = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr");
        $this->assertEquals(2, $leadListsTableRows->count());
        $secondColumnOfLine    = $leadListsTableRows->first()->filterXPath('//td[2]//div//i[@class="ri-fw ri-filter-2-fill fs-14"]')->count();
        $this->assertEquals(1, $secondColumnOfLine);
        $secondColumnOfLine    = $leadListsTableRows->eq(1)->filterXPath('//td[2]//div//i[@class="ri-fw ri-filter-2-fill fs-14"]')->count();
        $this->assertEquals(0, $secondColumnOfLine);
    }

    public function testSegmentWarningIcon(): void
    {
        $segmentWithOldLastRebuildDate            = $this->saveSegment('Lead List 1', 'lead-list-1');
        $segmentWithFreshLastRebuildDate          = $this->saveSegment('Lead List 2', 'lead-list-2');
        $segmentWithOldLastRebuildDateUnpublished = $this->saveSegment('Lead List 3', 'lead-list-3');

        $segmentWithOldLastRebuildDate->setLastBuiltDate(new \DateTime('-1 year'));
        $segmentWithFreshLastRebuildDate->setLastBuiltDate(new \DateTime('now'));
        $segmentWithOldLastRebuildDateUnpublished->isPublished(false);

        $this->em->persist($segmentWithOldLastRebuildDate);
        $this->em->persist($segmentWithFreshLastRebuildDate);
        $this->em->persist($segmentWithOldLastRebuildDateUnpublished);

        $this->em->flush();

        // Check segment count UI for no contacts.
        $crawler            = $this->client->request(Request::METHOD_GET, '/s/segments');
        $leadListsTableRows = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr");
        $this->assertEquals(3, $leadListsTableRows->count());
        $secondColumnOfLine    = $leadListsTableRows->first()->filterXPath('//td[2]//div//i[@class="text-danger ri-error-warning-line-circle fs-14"]')->count();
        $this->assertEquals(1, $secondColumnOfLine);
        $secondColumnOfLine    = $leadListsTableRows->eq(1)->filterXPath('//td[2]//div//i[@class="text-danger ri-error-warning-line-circle fs-14"]')->count();
        $this->assertEquals(0, $secondColumnOfLine);
        $secondColumnOfLine    = $leadListsTableRows->eq(2)->filterXPath('//td[2]//div//i[@class="text-danger ri-error-warning-line-circle fs-14"]')->count();
        $this->assertEquals(0, $secondColumnOfLine);
    }

    /**
     * @dataProvider dateFieldProvider
     */
    public function testWarningOnInvalidDateField(?string $filter, bool $shouldContainError, string $operator = '='): void
    {
        $segment = $this->saveSegment(
            'Date Segment',
            'ds',
            [
                [
                    'glue'     => 'and',
                    'field'    => 'date_added',
                    'object'   => 'lead',
                    'type'     => 'date',
                    'filter'   => $filter,
                    'display'  => null,
                    'operator' => $operator,
                ],
            ]
        );

        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$segment->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        if ($shouldContainError) {
            $this->assertStringContainsString('Date field filter value &quot;'.$filter.'&quot; is invalid', $this->client->getResponse()->getContent());
        } else {
            $this->assertStringNotContainsString('Date field filter value', $this->client->getResponse()->getContent());
        }
    }

    /**
     * @return array<int, array<int, bool|string|null>>
     */
    public static function dateFieldProvider(): array
    {
        return [
            ['Today', true],
            ['birthday', false],
            ['2023-01-01 11:00', false],
            ['2023-01-01 11:00:00', false],
            ['2023-01-01', false],
            ['next week', false],
            [null, false],
            ['\b\d{4}-(10|11|12)-\d{2}\b', false, 'regexp'],
        ];
    }
}
