<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Assert;
use Psr\Cache\InvalidArgumentException;
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
        $this->listModel = self::$container->get('mautic.lead.model.list');
        \assert($this->listModel instanceof ListModel);
        $this->listRepo = $this->listModel->getRepository();
        \assert($this->listRepo instanceof LeadListRepository);
        /** @var LeadModel $leadModel */
        $leadModel = self::$container->get('mautic.lead.model.lead');
        /* @var LeadRepository $leadRepo */
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
        $segment = $segment ?? new LeadList();
        $segment->setName($name)->setAlias($alias)->setFilters($filters);
        $this->listModel->saveEntity($segment);

        return $segment;
    }

    /**
     * @throws ORMException
     * @throws InvalidArgumentException
     */
    public function testSegmentCountOnPageLoad(): void
    {
        $segment = $this->saveSegment();
        $id      = $segment->getId();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $content = $crawler->filter('a.col-count')->filter('a[data-id="'.$id.'"]')->html();

        self::assertSame('No Contacts', trim($content));
    }

    /**
     * @throws ORMException
     * @throws InvalidArgumentException
     */
    public function testSegmentCountOnAjax(): void
    {
        $segment = $this->saveSegment2();
        $id      = $segment->getId();

        $parameter = ['id' => $id];
        $response  = $this->callAjaxRequest($parameter);

        self::assertSame('View 4 Contacts', $response['content']['html']);
        self::assertSame(4, $response['content']['leadCount']);
        self::assertSame(Response::HTTP_OK, $response['statusCode']);

        // check count after ajax on page load
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $content = $crawler->filter('a.col-count')->filter('a[data-id="'.$id.'"]')->html();

        self::assertSame('View 4 Contacts', trim($content));

        // remove contact from segment
        $segment = $this->removeContactFromSegment();
        $id      = $segment->getId();

        // check count from cache after contact removed
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $content = $crawler->filter('a.col-count')->filter('a[data-id="'.$id.'"]')->html();

        self::assertSame('View 3 Contacts', trim($content));
    }

    public function testSegmentNotFoundOnAjax(): void
    {
        // emulate invalid request parameter
        $parameter = ['id' => 'ABC'];
        $response  = $this->callAjaxRequest($parameter);

        self::assertSame('No Contacts', $response['content']['html']);
        self::assertSame(0, $response['content']['leadCount']);
        self::assertSame(Response::HTTP_NOT_FOUND, $response['statusCode']);
    }

    /**
     * @throws ORMException
     * @throws InvalidArgumentException
     */
    private function saveSegment2(): LeadList
    {
        $segment = new LeadList();
        $segment->setName('Lead List 1');
        $this->listModel->saveEntity($segment);

        $contactA = new Lead();
        $contactA->setFirstname('Contact A');

        $contactB = new Lead();
        $contactB->setFirstname('Contact B');

        $contactC = new Lead();
        $contactC->setFirstname('Contact C');

        $contactD = new Lead();
        $contactD->setFirstname('Contact D');

        $contacts = [$contactA, $contactB, $contactC, $contactD];

        $this->leadRepo->saveEntities($contacts);

        // add in cache
        $this->listModel->addLead($contactA, $segment);
        $this->listModel->addLead($contactB, $segment);
        $this->listModel->addLead($contactC, $segment);
        $this->listModel->addLead($contactD, $segment);

        return $segment;
    }

    /**
     * @throws ORMException
     * @throws InvalidArgumentException
     */
    private function removeContactFromSegment(): LeadList
    {
        $this->saveSegment2();

        $segment  = $this->listRepo->findOneBy(['name' => 'Lead List 1']);
        $contactA = $this->leadRepo->findOneBy(['firstname' => 'Contact A']);

        // remove from cache
        $this->listModel->removeLead($contactA, $segment);

        return $segment;
    }

    private function callAjaxRequest(array $parameter): array
    {
        $this->client->request(Request::METHOD_POST, '/s/ajax?action=lead:getLeadCount', $parameter);
        $clientResponse = $this->client->getResponse();

        return [
            'content'    => json_decode($clientResponse->getContent(), true),
            'statusCode' => $this->client->getResponse()->getStatusCode(),
        ];
    }
}
