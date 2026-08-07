<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\SegmentCountCacheCommand;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Model\ProjectModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ListControllerFunctionalTest extends MauticMysqlTestCase
{
    private ListModel $listModel;

    private LeadListRepository $listRepo;

    private SegmentCountCacheHelper $segmentCountCacheHelper;

    private LeadRepository $leadRepo;

    private TranslatorInterface $translator;

    private string $prefix;

    protected function setUp(): void
    {
        $this->configParams['update_segment_contact_count_in_background'] = 'testSegmentCountInBackground' === $this->name();
        $this->configParams['delete_segment_in_background']               = false;
        parent::setUp();
        $this->listModel = self::getContainer()->get(ListModel::class);
        $this->assertInstanceOf(ListModel::class, $this->listModel);
        $this->listRepo = $this->listModel->getRepository();
        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get(LeadModel::class);
        $this->assertInstanceOf(LeadModel::class, $leadModel);
        $this->segmentCountCacheHelper = self::getContainer()->get(SegmentCountCacheHelper::class);
        $this->leadRepo                = $leadModel->getRepository();
        $this->prefix                  = self::getContainer()->getParameter('mautic.db_table_prefix');
        $this->translator              = self::getContainer()->get(TranslatorInterface::class);
        $this->assertInstanceOf(TranslatorInterface::class, $this->translator);
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
        self::assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('#leadlist_filters_0_operator option')->count());
    }

    public function testSegmentWithProject(): void
    {
        $filters = [
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

        $segment = $this->saveSegment('Segment with Project', 'st1', $filters);

        $project = new Project();
        $project->setName('Test Project');

        $projectModel = self::getContainer()->get(ProjectModel::class);
        $this->assertInstanceOf(ProjectModel::class, $projectModel);
        $projectModel->saveEntity($project);

        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$segment->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedSegment = $this->listRepo->find($segment->getId());
        $this->assertInstanceOf(LeadList::class, $savedSegment);
        $this->assertSame($project->getId(), $savedSegment->getProjects()->first()->getId());
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
        $this->segmentCountCacheHelper->deleteSegmentContactCount($segmentId);

        // Save manual segment without filters.
        $manualSegment   = $this->saveSegment('Lead List 2', 'lead-list-2', []);
        $manualSegmentId = $manualSegment->getId();

        // Verify last built date is not set.
        $this->assertNotInstanceOf(\DateTimeInterface::class, $segment->getLastBuiltDate());

        // Check segment count UI for no contacts for manual segment.
        // And check the filtered segment is Building
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        $spClass = $this->getSegmentCountClass($crawler, $segmentId);
        $this->assertSame('Building', $html);
        $this->assertSame('label label-info col-count', $spClass);
        $html    = $this->getSegmentCountHtml($crawler, $manualSegmentId);
        $spClass = $this->getSegmentCountClass($crawler, $manualSegmentId);
        $this->assertSame('No Contacts', $html);
        $this->assertSame('label label-gray col-count', $spClass);

        // Add 4 contacts.
        $contacts   = $this->saveContacts();
        $contact1Id = $contacts[0]->getId();

        // Rebuild segment - set current count to the cache.
        $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segmentId, '--env' => 'test']);

        // Verify last built date is set.
        $this->em->detach($segment);
        $segment = $this->listRepo->find($segmentId);
        $this->assertInstanceOf(LeadList::class, $segment);
        $this->assertInstanceOf(\DateTimeInterface::class, $segment->getLastBuiltDate());

        // Set last built date in the future to allow testing without waiting.
        // (Same second built date as the modified date is shown as "Building" still in the UI).
        $segment->setLastBuiltDate(new \DateTime('+5 seconds'));
        $this->listModel->saveEntity($segment);

        // Check segment count UI for 4 contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        $spClass = $this->getSegmentCountClass($crawler, $segmentId);
        $this->assertSame('View 4 Contacts', $html);
        $this->assertSame('label label-gray col-count', $spClass);

        // Remove 1 contact from segment.
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contact/'.$contact1Id.'/remove');
        $this->assertSame('{"success":1}', $this->client->getResponse()->getContent());
        $this->assertResponseIsSuccessful();

        // Check segment count UI for 3 contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        $spClass = $this->getSegmentCountClass($crawler, $segmentId);
        $this->assertSame('View 3 Contacts', $html);
        $this->assertSame('label label-gray col-count', $spClass);

        // Add 1 contact back to segment.
        $parameters = ['ids' => [$contact1Id]];
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contacts/add', $parameters);
        $this->assertSame('{"success":1,"details":{"'.$contact1Id.'":{"success":true}}}', $this->client->getResponse()->getContent());
        $this->assertResponseIsSuccessful();

        // Check segment count UI for 4 contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        $spClass = $this->getSegmentCountClass($crawler, $segmentId);
        $this->assertSame('View 4 Contacts', $html);
        $this->assertSame('label label-gray col-count', $spClass);

        // Check segment count AJAX for 4 contacts.
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        $this->assertSame('View 4 Contacts', $response['content']['html']);
        $this->assertSame('label label-gray col-count', $response['content']['className']);
        $this->assertSame(4, $response['content']['leadCount']);
        $this->assertSame(Response::HTTP_OK, $response['statusCode']);

        // Remove 1 contact from segment.
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contact/'.$contact1Id.'/remove');
        $this->assertSame('{"success":1}', $this->client->getResponse()->getContent());
        $this->assertResponseIsSuccessful();

        // Check segment count AJAX for 3 contacts.
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        $this->assertSame('View 3 Contacts', $response['content']['html']);
        $this->assertSame('label label-gray col-count', $response['content']['className']);
        $this->assertSame(3, $response['content']['leadCount']);
        $this->assertSame(Response::HTTP_OK, $response['statusCode']);

        // Add 1 contact back to segment.
        $parameters = ['ids' => [$contact1Id]];
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contacts/add', $parameters);
        $this->assertSame('{"success":1,"details":{"'.$contact1Id.'":{"success":true}}}', $this->client->getResponse()->getContent());
        $this->assertResponseIsSuccessful();

        // Check segment count AJAX for 4 contacts.
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        $this->assertSame('View 4 Contacts', $response['content']['html']);
        $this->assertSame('label label-gray col-count', $response['content']['className']);
        $this->assertSame(4, $response['content']['leadCount']);
        $this->assertSame(Response::HTTP_OK, $response['statusCode']);

        // Save filtered segment again to trigger rebuild label, setting last built date in the past.
        $this->em->detach($segment);
        $segment = $this->listRepo->find($segmentId);
        $this->assertInstanceOf(LeadList::class, $segment);
        $segment->setLastBuiltDate(new \DateTime('-1 year'));
        // Date modified only updates on specific changes, so change name.
        $segment->setName('Lead List 1 Updated');
        $this->listModel->saveEntity($segment);

        // Check segment count UI for bulding with 4 contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        $spClass = $this->getSegmentCountClass($crawler, $segmentId);
        $this->assertSame('Building (4 Contacts)', $html);
        $this->assertSame('label label-info col-count', $spClass);

        // Check segment count AJAX for building 4 contacts.
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        $this->assertSame('Building (4 Contacts)', $response['content']['html']);
        $this->assertSame('label label-info col-count', $response['content']['className']);
        $this->assertSame(4, $response['content']['leadCount']);
        $this->assertSame(Response::HTTP_OK, $response['statusCode']);
    }

    /**
     * @throws \Exception
     */
    public function testSegmentCountInBackground(): void
    {
        // Save segment.
        $filters = [
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
        $this->segmentCountCacheHelper->deleteSegmentContactCount($segmentId);

        // Check segment count UI for no contacts.
        usleep(1000000);
        $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segmentId, '--env' => 'test']);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        $this->assertSame('No Contacts', $html);

        // Add 4 contacts.
        $contacts   = $this->saveContacts();
        $contact1Id = $contacts[0]->getId();

        // Rebuild segment - set current count to the cache.
        $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segmentId, '--env' => 'test']);

        $this->testSymfonyCommand(SegmentCountCacheCommand::COMMAND_NAME);

        // Check segment count UI for 4 contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        $this->assertSame('View 4 Contacts', $html);

        // Remove 1 contact from segment.
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contact/'.$contact1Id.'/remove');
        $this->assertSame('{"success":1}', $this->client->getResponse()->getContent());
        self::assertResponseIsSuccessful();

        $this->testSymfonyCommand(SegmentCountCacheCommand::COMMAND_NAME);

        // Check segment count UI for 3 contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        $this->assertSame('View 3 Contacts', $html);

        // Add 1 contact back to segment.
        $parameters = ['ids' => [$contact1Id]];
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contacts/add', $parameters);
        $this->assertSame('{"success":1,"details":{"'.$contact1Id.'":{"success":true}}}', $this->client->getResponse()->getContent());
        self::assertResponseIsSuccessful();

        $this->testSymfonyCommand(SegmentCountCacheCommand::COMMAND_NAME);

        // Check segment count UI for 4 contacts.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        $this->assertSame('View 4 Contacts', $html);

        // Check segment count AJAX for 4 contacts.
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        $this->assertSame('View 4 Contacts', $response['content']['html']);
        $this->assertSame(4, $response['content']['leadCount']);
        $this->assertSame(Response::HTTP_OK, $response['statusCode']);

        // Remove 1 contact from segment.
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contact/'.$contact1Id.'/remove');
        $this->assertSame('{"success":1}', $this->client->getResponse()->getContent());
        $this->assertResponseIsSuccessful();

        $this->testSymfonyCommand(SegmentCountCacheCommand::COMMAND_NAME);

        // Check segment count AJAX for 3 contacts.
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        $this->assertSame('View 3 Contacts', $response['content']['html']);
        $this->assertSame(3, $response['content']['leadCount']);
        $this->assertSame(Response::HTTP_OK, $response['statusCode']);

        // Add 1 contact back to segment.
        $parameters = ['ids' => [$contact1Id]];
        $this->client->request(Request::METHOD_POST, '/api/segments/'.$segmentId.'/contacts/add', $parameters);
        $this->assertSame('{"success":1,"details":{"'.$contact1Id.'":{"success":true}}}', $this->client->getResponse()->getContent());
        $this->assertResponseIsSuccessful();

        $this->testSymfonyCommand(SegmentCountCacheCommand::COMMAND_NAME);

        // Check segment count AJAX for 4 contacts.
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        $this->assertSame('View 4 Contacts', $response['content']['html']);
        $this->assertSame(4, $response['content']['leadCount']);
        $this->assertSame(Response::HTTP_OK, $response['statusCode']);
    }

    public function testSegmentClone(): void
    {
        $segment   = $this->saveSegment('Test Segment', 'testsegment');
        $segmentId = $segment->getId();

        // Number of segments before clone
        $segmentsCountBefore = $this->em->getRepository(LeadList::class)->count([]);
        // Go to clone segment action
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.$segmentId);
        $this->assertResponseIsSuccessful();
        // First submit
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        // Second submit
        $form = $crawler->selectButton('leadlist_buttons_apply')->form();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        // Number of segments after clone
        $segmentsCountAfter = $this->em->getRepository(LeadList::class)->count([]);
        // Check that just one segment was created
        $this->assertSame($segmentsCountBefore + 1, $segmentsCountAfter);
    }

    public function testSegmentAliasCreation(): void
    {
        $segment   = $this->saveSegment('Test Segment Alias', 'test-segment-alias');
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
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.$segmentId);
        $this->assertResponseIsSuccessful();
        // Save cloned segment
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        return $crawler->filter('#leadlist_alias')->attr('value');
    }

    public function testSegmentNotFoundOnAjax(): void
    {
        // Emulate invalid request parameter.
        $parameter = ['id' => 'ABC'];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);

        $this->assertSame('No Contacts', $response['content']['html']);
        $this->assertSame(0, $response['content']['leadCount']);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response['statusCode']);
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
        $expectedErrorMessage = sprintf('The segment %s is used in %s, please go back and check segments before unpublishing', $list1->getName(), $list2->getName());

        $this->client->request(Request::METHOD_POST, '/s/ajax', ['action' => 'togglePublishStatus', 'model' => 'lead.list', 'id' => $list1->getId()]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString($expectedErrorMessage, (string) $this->client->getResponse()->getContent());
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$list1->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[isPublished]']->setValue('0');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($expectedErrorMessage, (string) $this->client->getResponse()->getContent());
    }

    public function testUnpublishUnUsedSegment(): void
    {
        $list1 = $this->saveSegment('s1', 's1');
        $list2 = $this->saveSegment('s2', 's2');
        $this->em->clear();

        $this->client->request(Request::METHOD_POST, '/s/ajax', ['action' => 'togglePublishStatus', 'model' => 'lead.list', 'id' => $list1->getId()]);
        $this->assertResponseIsSuccessful();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$list2->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[isPublished]']->setValue('0');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $rows = $this->listRepo->findAll();
        $this->assertCount(2, $rows);
        $this->assertFalse($rows[0]->isPublished());
        $this->assertFalse($rows[1]->isPublished());
    }

    public function testDeleteUsedInCampaignSegment(): void
    {
        $list1  = $this->saveSegment('s1', 's1');

        $campaign     = new Campaign();
        $campaignName = 'Campaign1';
        $campaign->setName($campaignName);

        $this->em->persist($campaign);
        $this->em->flush();

        // insert unpublished record
        $this->connection->insert($this->prefix.'campaign_leadlist_xref', [
            'campaign_id'   => $campaign->getId(),
            'leadlist_id'   => $list1->getId(),
        ]);

        $expectedErrorMessage = $this->translator->trans(
            'mautic.lead.lists.used_in_campaigns.delete',
            [
                '%campaignNames%' => '"'.$campaignName.'"',
                '%segmentNames%'  => 's1',
                '%count%'         => 1,
            ],
            'validators'
        );

<<<<<<< HEAD
<<<<<<< HEAD
        $this->client->request(Request::METHOD_POST, 's/segments/delete/'.$list1->getId(), [], [], $this->createAjaxHeaders());
=======
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, 's/segments/delete/'.$list1->getId(), [], [], $this->createAjaxHeaders());
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $this->client->request(Request::METHOD_POST, 's/segments/delete/'.$list1->getId(), [], [], $this->createAjaxHeaders());
>>>>>>> 222589fde5 (cs)

        $clientResponse     = $this->client->getResponse();
        $clientResponseBody = json_decode($clientResponse->getContent(), true);

        $this->assertStringContainsString($expectedErrorMessage, (string) $clientResponseBody['flashes']);
    }

    public function testBatchDeleteUsedInCampaignSegment(): void
    {
        $list1  = $this->saveSegment('s1', 's1');
        $list2  = $this->saveSegment('s2', 's2');

        $campaign     = new Campaign();
        $campaignName = 'Campaign1';
        $campaign->setName($campaignName);

        $this->em->persist($campaign);
        $this->em->flush();

        // insert unpublished record
        $this->connection->insert($this->prefix.'campaign_leadlist_xref', [
            'campaign_id'   => $campaign->getId(),
            'leadlist_id'   => $list1->getId(),
        ]);
        $this->connection->insert($this->prefix.'campaign_leadlist_xref', [
            'campaign_id'   => $campaign->getId(),
            'leadlist_id'   => $list2->getId(),
        ]);

        $expectedErrorMessage = $this->translator->trans(
            'mautic.lead.list.error.cannot.delete.batch',
            [
                '%segments%'  => $list1->getName().', '.$list2->getName(),
            ],
            'flashes'
        );

        $parameters = 'ids=["'.$list1->getId().'","'.$list2->getId().'"]';
<<<<<<< HEAD
<<<<<<< HEAD
        $this->client->request(Request::METHOD_POST, 's/segments/batchDelete?'.$parameters, [], [], $this->createAjaxHeaders());
=======
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, 's/segments/batchDelete?'.$parameters, [], [], $this->createAjaxHeaders());
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $this->client->request(Request::METHOD_POST, 's/segments/batchDelete?'.$parameters, [], [], $this->createAjaxHeaders());
>>>>>>> 222589fde5 (cs)

        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $clientResponseBody = json_decode($clientResponse->getContent(), true);

        $this->assertStringContainsString($expectedErrorMessage, (string) $clientResponseBody['flashes']);
    }

    /**
     * @param array<int, array<string, mixed>>|null $filters
     */
    private function saveSegment(string $name, string $alias, ?array $filters = null, ?LeadList $segment = null): LeadList
    {
        $segment ??= new LeadList();
        $filters ??= $this->defaultFilter();
        $segment->setName($name)->setAlias($alias)->setFilters($filters);
        $this->listModel->saveEntity($segment);

        return $segment;
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
        $content = $crawler->filter('span.col-count[data-id="'.$id.'"] a')->html();

        return trim($content);
    }

    private function getSegmentCountClass(Crawler $crawler, int $id): string
    {
        $class = $crawler->filter('span.col-count[data-id="'.$id.'"]')->attr('class');

        return trim($class);
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
        $this->assertResponseIsSuccessful();

        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[alias]']->setValue('clonesegment2');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

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
        $this->saveSegment('Lead List 2', 'lead-list-2', []);

        // Check segment count UI for no contacts.
        $crawler            = $this->client->request(Request::METHOD_GET, '/s/segments');
        $leadListsTableRows = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr");
        $this->assertCount(2, $leadListsTableRows);

        // Find rows by segment name to avoid relying on table order
        $rowWithFilters    = null;
        $rowWithoutFilters = null;
        foreach ($leadListsTableRows as $row) {
            $rowCrawler = new Crawler($row);
            $nameText   = $rowCrawler->filterXPath('.//td[2]//a')->text();
            if (str_contains($nameText, 'Lead List 1')) {
                $rowWithFilters = $rowCrawler;
            } elseif (str_contains($nameText, 'Lead List 2')) {
                $rowWithoutFilters = $rowCrawler;
            }
        }

        $this->assertInstanceOf(Crawler::class, $rowWithFilters, 'Could not find Lead List 1 row');
        $this->assertInstanceOf(Crawler::class, $rowWithoutFilters, 'Could not find Lead List 2 row');

        // Lead List 1 (with filters) should have the filter icon
        $filterIconCount = $rowWithFilters->filterXPath('.//td[2]//div//i[@class="ri-fw ri-filter-2-fill fs-14"]')->count();
        $this->assertSame(1, $filterIconCount);

        // Lead List 2 (without filters) should NOT have the filter icon
        $filterIconCount = $rowWithoutFilters->filterXPath('.//td[2]//div//i[@class="ri-fw ri-filter-2-fill fs-14"]')->count();
        $this->assertSame(0, $filterIconCount);
    }

    public function testUnpublishedSegmentDoesNotShowRebuildingLabel(): void
    {
        // Create a segment that would normally show "Building" label
        $segment = $this->saveSegment('Unpublished Segment', 'unpublished-segment', [
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'operator' => '!empty',
                'display'  => '',
            ],
        ]);

        // Set last built date in the past to trigger "Building" label for published segments
        $segment->setLastBuiltDate(new \DateTime('-1 year'));

        // Unpublish the segment - this should prevent "Building" label
        $segment->setIsPublished(false);
        $this->listModel->saveEntity($segment);
        $this->em->clear();

        $segmentId = $segment->getId();

        // Check segment count UI - should show "No Contacts" rather than "Building"
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $html    = $this->getSegmentCountHtml($crawler, $segmentId);
        $spClass = $this->getSegmentCountClass($crawler, $segmentId);
        $this->assertSame('No Contacts', $html);
        $this->assertSame('label label-gray col-count', $spClass);

        // Check segment count AJAX - should also show "No Contacts"
        $parameter = ['id' => $segmentId];
        $response  = $this->callGetLeadCountAjaxRequest($parameter);
        $this->assertSame('No Contacts', $response['content']['html']);
        $this->assertSame('label label-gray col-count', $response['content']['className']);
        $this->assertSame(0, $response['content']['leadCount']);
        $this->assertSame(Response::HTTP_OK, $response['statusCode']);
    }

    public function testSegmentWarningIcon(): void
    {
        $segmentWithOldLastRebuildDate            = $this->saveSegment('TEST-Warning-Segment', 'test-warning-segment');
        $segmentWithFreshLastRebuildDate          = $this->saveSegment('TEST-Fresh-Segment', 'test-fresh-segment');
        $segmentUnpublished                       = $this->saveSegment('TEST-Unpublished-Segment', 'test-unpublished-segment');

        $segmentWithOldLastRebuildDate->setLastBuiltDate(new \DateTime('-1 year'));
        $segmentWithFreshLastRebuildDate->setLastBuiltDate(new \DateTime('now'));
        $segmentUnpublished->setIsPublished(false);

        $this->em->persist($segmentWithOldLastRebuildDate);
        $this->em->persist($segmentWithFreshLastRebuildDate);
        $this->em->persist($segmentUnpublished);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');

        $warningSegmentRow = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr[contains(., 'TEST-Warning-Segment')]");
        $warningIcon       = $warningSegmentRow->filterXPath('.//i[@class="text-danger ri-error-warning-line fs-14"]');
        $this->assertCount(1, $warningIcon);

        $freshSegmentRow = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr[contains(., 'TEST-Fresh-Segment')]");
        $warningIcon     = $freshSegmentRow->filterXPath('.//i[@class="text-danger ri-error-warning-line fs-14"]');
        $this->assertCount(0, $warningIcon);

        $unpublishedSegmentRow = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr[contains(., 'TEST-Unpublished-Segment')]");
        $warningIcon           = $unpublishedSegmentRow->filterXPath('.//i[@class="text-danger ri-error-warning-line fs-14"]');
        $this->assertCount(0, $warningIcon);
    }

    public function testBatchDeleteWithEmptyMembership(): void
    {
        $segment = $this->saveSegment(
            'Empty Members',
            'empty-members',
            [
                [
                    'glue'     => 'and',
                    'field'    => 'leadlist',
                    'object'   => 'lead',
                    'type'     => 'leadlist',
                    'filter'   => null,
                    'display'  => null,
                    'operator' => 'empty',
                ],
            ]
        );

        $segmentId = $segment->getId();

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest('POST', "s/segments/batchDelete?ids=[\"{$segmentId}\"]");

        $clientResponse = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('1 segments have been deleted!', (string) $clientResponse->getContent());

        $this->em->clear();

        $segmentExistCheck = $this->listRepo->find($segmentId);
        $this->assertNotInstanceOf(LeadList::class, $segmentExistCheck);
    }

    #[DataProvider('dateFieldProvider')]
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

        $this->assertStringContainsString('leadlist_buttons_apply', (string) $this->client->getResponse()->getContent());

        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        if ($shouldContainError) {
            $this->assertStringContainsString('Date field filter value &quot;'.$filter.'&quot; is invalid', (string) $this->client->getResponse()->getContent());
        } else {
            $this->assertStringNotContainsString('Date field filter value', (string) $this->client->getResponse()->getContent());
        }
    }

    /**
     * @return \Iterator<int, array<int, (bool|string|null)>>
     */
    public static function dateFieldProvider(): \Iterator
    {
        yield ['Today', false];
        yield ['Not-a-date', true];
        yield ['birthday', false];
        yield ['2023-01-01 11:00', false];
        yield ['2023-01-01 11:00:00', false];
        yield ['2023-01-01', false];
        yield ['next week', false];
        yield [null, false];
        yield ['\b\d{4}-(10|11|12)-\d{2}\b', false, 'regexp'];
    }

    public function testRecentActivityFeedOnSegmentDetailsPage(): void
    {
        // Create segment
        $segment = $this->saveSegment('Date Segment', 'ds');
        $this->em->clear();

        // Update segment
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$segment->getId());
        $this->assertResponseIsSuccessful();
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[isPublished]']->setValue('0');
        $this->client->submit($form);

        // View segment
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/view/'.$segment->getId());
        $this->assertResponseIsSuccessful();

        $translator = self::getContainer()->get(TranslatorInterface::class);

        $this->assertStringContainsString($translator->trans('mautic.core.recent.activity'), (string) $this->client->getResponse()->getContent());
        $this->assertCount(2, $crawler->filterXPath('//ul[contains(@class, "media-list-feed")]/li'));
    }

    public function testActiveContactsStatExcludesDnc(): void
    {
        $segment  = $this->saveSegment('active-test', 'active-test');
        $contact1 = new Lead();
        $contact1->setFirstname('Active');
        $this->em->persist($contact1);
        $contact2 = new Lead();
        $contact2->setFirstname('DNC');
        $this->em->persist($contact2);
        $this->em->flush();
        $segmentContact1 = new ListLead();
        $segmentContact1->setList($segment);
        $segmentContact1->setLead($contact1);
        $segmentContact1->setDateAdded(new \DateTime());
        $segmentContact1->setManuallyAdded(false);
        $segmentContact1->setManuallyRemoved(false);
        $this->em->persist($segmentContact1);
        $segmentContact2 = new ListLead();
        $segmentContact2->setList($segment);
        $segmentContact2->setLead($contact2);
        $segmentContact2->setDateAdded(new \DateTime());
        $segmentContact2->setManuallyAdded(false);
        $segmentContact2->setManuallyRemoved(false);
        $this->em->persist($segmentContact2);
        $this->em->flush();
        $dnc = new DoNotContact();
        $dnc->setChannel('email');
        $dnc->setLead($contact2);
        $dnc->setReason(DoNotContact::UNSUBSCRIBED);
        $dnc->setDateAdded(new \DateTime());
        $this->em->persist($dnc);
        $this->em->flush();
<<<<<<< HEAD
<<<<<<< HEAD
        $this->client->request(Request::METHOD_GET, sprintf('/s/segments/view/%d', $segment->getId()));
=======
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, sprintf('/s/segments/view/%d', $segment->getId()));
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $this->client->request(Request::METHOD_GET, sprintf('/s/segments/view/%d', $segment->getId()));
>>>>>>> 222589fde5 (cs)
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $html = $response->getContent();
        $this->assertStringContainsString('Total contacts', (string) $html);
        $this->assertStringContainsString('2', (string) $html);
        $this->assertStringContainsString('Active contacts', (string) $html);
        $this->assertStringContainsString('1', (string) $html);
    }

    /**
     * @param array<mixed> $filter
     */
    #[DataProvider('relativeDateInvalidIntervalValues')]
    public function testSegmentRelativeDateFilterOnlySupportPositiveNumber(string $operator, array $filter, string $interval, string $message): void
    {
        $filterData = [[
            'glue'        => 'and',
            'field'       => 'date_identified',
            'object'      => 'lead',
            'type'        => 'datetime',
            'operator'    => $operator,
            'properties'  => [
                'filter' => $filter,
            ],
        ]];
        $list = $this->saveSegment('s1', 's1', $filterData);
        $this->em->clear();

        $this->client->request(Request::METHOD_POST, '/s/ajax', ['action' => 'togglePublishStatus', 'model' => 'lead.list', 'id' => $list->getId()]);
        $this->assertTrue($this->client->getResponse()->isOk());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$list->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[filters][0][properties][filter][interval]']->setValue($interval);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->assertStringContainsString($message, (string) $this->client->getResponse()->getContent());

        $rows = $this->listRepo->findAll();
        $this->assertCount(1, $rows);
    }

    public static function relativeDateInvalidIntervalValues(): iterable
    {
        yield [
            'inLast',
            [
                'interval' => '1',
                'unit'     => 'day',
            ],
            '-2',
            'This value should be positive.',
        ];

        yield [
            'inNext',
            [
                'interval' => '1',
                'unit'     => 'day',
            ],
            'foo',
            'Please enter an integer.',
        ];

        yield [
            'inLast',
            [
                'interval' => '1',
                'unit'     => 'day',
            ],
            '2.5',
            'Please enter an integer.',
        ];

        yield [
            'inLast',
            [
                'interval' => '',
                'unit'     => 'day',
            ],
            '',
            'This value should not be blank.',
        ];
    }

    /**
     * @return array<mixed>
     */
    private function defaultFilter(): array
    {
        return [[
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '!empty',
            'display'  => '',
        ]];
    }
}
