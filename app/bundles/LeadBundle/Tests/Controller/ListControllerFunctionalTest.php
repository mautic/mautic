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
        /* @var ListModel $listModel */
        $this->listModel = $this->container->get('mautic.lead.model.list');
        /* @var LeadListRepository listRepo */
        $this->listRepo = $this->listModel->getRepository();
        /** @var LeadModel $leadModel */
        $leadModel = $this->container->get('mautic.lead.model.lead');
        /* @var LeadRepository $leadRepo */
        $this->leadRepo = $leadModel->getRepository();
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
        $this->runCommand('mautic:segments:update', ['-i' => $segmentId, '--env' => 'test']);

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

    public function testSegmentClone(): void
    {
        $segment = new LeadList();
        $segment->setName('Test Segment');
        $segment->setAlias('testsegment');
        $this->em->persist($segment);
        $this->em->flush();
        $segmentId = $segment->getId();

        // Number of segments before clone
        $segmentsCountBefore = $this->em->getRepository(LeadList::class)->count([]);
        // Go to clone segment action
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.(string) $segmentId);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        // First submit
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), 'Correct Apply');
        // Second submit
        $form = $crawler->selectButton('leadlist_buttons_apply')->form();
        $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), 'Correct Apply');
        // Number of segments after clone
        $segmentsCountAfter = $this->em->getRepository(LeadList::class)->count([]);
        // Check that just one segment was created
        $this->assertSame($segmentsCountBefore + 1, $segmentsCountAfter);
    }

    public function testSegmentAliasCreation(): void
    {
        $segment = new LeadList();
        $segment->setName('Test Segment Alias');
        $segment->setAlias('test-segment-alias');
        $this->em->persist($segment);
        $this->em->flush();
        $segmentId = $segment->getId();

        // Clone segment
        $aliasFirst = $this->getAliasWhenCloneSegment($segmentId);
        // Clone segment again
        $aliasSecond = $this->getAliasWhenCloneSegment($segmentId);
        // Check that aliases are not the same
        $this->assertNotSame($aliasFirst, $aliasSecond);
    }

    private function getAliasWhenCloneSegment(int $segmentId): string
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.(string) $segmentId);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        // Save cloned segment
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), 'Correct Apply');

        return $crawler->filter('#leadlist_alias')->attr('value');
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

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$list1->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[isPublished]']->setValue(0);
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString($expectedErrorMessage, $crawler->html());
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
        $form['leadlist[isPublished]']->setValue(0);
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $rows = $this->listRepo->findAll();
        $this->assertCount(2, $rows);
        $this->assertFalse($rows[0]->isPublished());
        $this->assertFalse($rows[1]->isPublished());
    }

    private function saveSegment(string $name, string $alias, array $filters = [], LeadList $segment = null): LeadList
    {
        $segment = $segment ?? new LeadList();
        $segment->setName($name)->setAlias($alias)->setFilters($filters);
        $this->listModel->saveEntity($segment);

        return $segment;
    }

    private function saveContacts($count = 4): array
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

    private function callGetLeadCountAjaxRequest(array $parameter): array
    {
        $this->client->request(Request::METHOD_POST, '/s/ajax?action=lead:getLeadCount', $parameter);
        $clientResponse = $this->client->getResponse();

        return [
            'content'    => json_decode($clientResponse->getContent(), true),
            'statusCode' => $this->client->getResponse()->getStatusCode(),
        ];
    }
}
