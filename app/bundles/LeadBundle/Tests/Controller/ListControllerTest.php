<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Symfony\Component\HttpFoundation\Response;

class ListControllerTest extends MauticMysqlTestCase
{
    use ControllerTrait;

    /**
     * Index action should return status code 200.
     */
    public function testIndexAction(): void
    {
        $list = $this->createList();

        $this->em->persist($list);
        $this->em->flush();
        $this->em->clear();

        $urlAlias   = 'segments';
        $routeAlias = 'leadlist';
        $column     = 'dateModified';
        $column2    = 'name';
        $tableAlias = 'l.';

        $this->getControllerColumnTests($urlAlias, $routeAlias, $column, $tableAlias, $column2);
    }

    /**
     * Check if list contains correct values.
     */
    public function testViewList(): void
    {
        $list = $this->createList();
        $list->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $list->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $list->setCreatedByUser('Test User');

        $this->em->persist($list);
        $this->em->flush();
        $this->em->clear();

        $this->client->request('GET', '/s/segments');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertStringContainsString('February 7, 2020', $clientResponse->getContent());
        $this->assertStringContainsString('March 21, 2020', $clientResponse->getContent());
        $this->assertStringContainsString('Test User', $clientResponse->getContent());
    }

    /**
     * Filtering should return status code 200.
     */
    public function testIndexActionWhenFiltering(): void
    {
        $this->client->request('GET', '/s/segments?search=has%3Aresults&tmpl=list');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
    }

    public function testSegmentView(): void
    {
        $contacts = $this->createContacts();
        $segment  = $this->addContactsToSegment($contacts, 'MySeg');
        $this->client->request('GET', sprintf('/s/segments/view/%d', $segment->getId()));
        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('MySeg', $response->getContent());
        //Make sure that contact grid is not loaded synchronously
        self::assertStringNotContainsString('Kane', $response->getContent());
        self::assertStringNotContainsString('Jacques', $response->getContent());
        //Make sure the data-target-url is not an absolute URL
        self::assertStringContainsString(sprintf('data-target-url="/s/segment/view/%s/contact/1"', $segment->getId()), $response->getContent());
    }

    public function testSegmentContactGrid(): void
    {
        $pageId   = 1;
        $contacts = $this->createContacts();
        $segment  = $this->addContactsToSegment($contacts, 'MySeg');
        $this->client->request('GET', sprintf('/s/segment/view/%d/contact/%d', $segment->getId(), $pageId));
        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Kane', $response->getContent());
        self::assertStringContainsString('Jacques', $response->getContent());
    }

    private function createList(string $suffix = 'A'): LeadList
    {
        $list = new LeadList();
        $list->setName("Segment $suffix");
        $list->setPublicName("Segment $suffix");
        $list->setAlias("segment-$suffix");
        $list->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $list->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $list->setCreatedByUser('Test User');

        return $list;
    }

    /**
     * @return Lead[]
     */
    private function createContacts(): array
    {
        $contact1 = new Lead();
        $contact1->setFirstname('Kane');
        $contact1->setLastname('Williamson');
        $contact1->setEmail('kane.williamson@test.com');

        $contact2 = new Lead();
        $contact2->setFirstname('Jacques');
        $contact2->setLastname('Kallis');
        $contact2->setEmail('jacques.kallis@test.com');

        $this->em->persist($contact1);
        $this->em->persist($contact2);
        $this->em->flush();

        return [$contact1, $contact2];
    }

    /**
     * @param Lead[] $contacts
     */
    private function addContactsToSegment(array $contacts, string $segmentName): LeadList
    {
        $filters = [
            'glue'       => 'and',
            'field'      => 'company',
            'object'     => 'lead',
            'type'       => 'text',
            'operator'   => 'contains',
            'properties' => [
                    'filter' => 'Acquia',
                ],
            'filter'  => 'Acquia',
            'display' => null,
        ];

        $segment = new LeadList();
        $segment->setName($segmentName);
        $segment->setPublicName($segmentName);
        $segment->setAlias(strtolower($segmentName));
        $segment->isPublished(true);
        $segment->setDateAdded(new \DateTime());
        $segment->setFilters($filters);
        $segment->setIsGlobal(true);
        $segment->setIsPreferenceCenter(false);
        $this->em->persist($segment);

        foreach ($contacts as $contact) {
            $segmentContacts = new ListLead();
            $segmentContacts->setList($segment);
            $segmentContacts->setLead($contact);
            $segmentContacts->setDateAdded(new \DateTime());
            $segmentContacts->setManuallyAdded(false);
            $segmentContacts->setManuallyRemoved(false);
            $this->em->persist($segmentContacts);
        }

        $this->em->flush();

        return $segment;
    }
}
