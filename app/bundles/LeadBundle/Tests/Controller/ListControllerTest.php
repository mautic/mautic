<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Symfony\Component\DomCrawler\Crawler;

final class ListControllerTest extends MauticMysqlTestCase
{
    /**
     * Index action should return status code 200.
     */
    public function testIndexAction(): void
    {
        $list = $this->createList();

        $this->em->persist($list);
        $this->em->flush();
        $this->em->clear();

        $crawler         = $this->client->request('GET', '/s/segments?tmpl=list&name=lead.list');
        $responseContent = (string) $this->client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('col-leadlist-dateAdded', $responseContent, 'The return must contain the created at date column');
        $this->assertStringContainsString('col-leadlist-dateModified', $responseContent, 'The return must contain the modified date column');
        $this->assertSortState($crawler, 'dateModified', 'descending');

        $crawler = $this->client->request('GET', '/s/segments?tmpl=list&name=lead.list&orderby=l.dateModified');
        $this->assertSortState($crawler, 'dateModified', 'ascending');

        $crawler = $this->client->request('GET', '/s/segments?tmpl=list&name=lead.list&orderby=l.name');
        $this->assertSortState($crawler, 'name', 'ascending');

        $crawler = $this->client->request('GET', '/s/segments?tmpl=list&name=lead.list&orderby=l.name');
        $this->assertSortState($crawler, 'name', 'descending');
    }

    private function assertSortState(Crawler $crawler, string $column, string $direction): void
    {
        $this->assertCount(
            1,
            $crawler->filterXPath(sprintf("//th[contains(@class,'col-leadlist-%s') and @aria-sort='%s']", $column, $direction)),
            sprintf('The %s column must be sorted %s', $column, $direction)
        );
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
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('February 7, 2020', (string) $clientResponse->getContent());
        $this->assertStringContainsString('March 21, 2020', (string) $clientResponse->getContent());
        $this->assertStringContainsString('Test User', (string) $clientResponse->getContent());
    }

    /**
     * Filtering should return status code 200.
     */
    public function testIndexActionWhenFiltering(): void
    {
        $this->client->request('GET', '/s/segments?search=has%3Aresults&tmpl=list');
        $this->assertResponseIsSuccessful('Return code must be 200.');
    }

    public function testSegmentView(): void
    {
        $contacts = $this->createContacts();
        $segment  = $this->addContactsToSegment($contacts, 'MySeg');
        $this->client->request('GET', sprintf('/s/segments/view/%d', $segment->getId()));
        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('MySeg', (string) $response->getContent());
        $this->assertStringNotContainsString('Kane', (string) $response->getContent());
        $this->assertStringNotContainsString('Jacques', (string) $response->getContent());
        $this->assertStringNotContainsString('contacts-container', (string) $response->getContent());
    }

    private function createList(string $suffix = 'A'): LeadList
    {
        $list = new LeadList();
        $list->setName("Segment {$suffix}");
        $list->setPublicName("Segment {$suffix}");
        $list->setAlias("segment-{$suffix}");
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
            [
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
            ],
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

    public function testCloneSegmentPage(): void
    {
        $list = $this->createList('clone');
        $list->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $list->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $list->setCreatedByUser('Test User');

        $this->em->persist($list);
        $this->em->flush();
        $this->em->clear();

        $this->client->request('GET', sprintf('/s/segments/clone/%d', $list->getId()));

        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful('Return code must be 200.');
        $this->assertStringContainsString('Segment clone', (string) $clientResponse->getContent());
    }

    public function testSegmentSearchFilters(): void
    {
        $this->createCustomField();
        $segment = $this->createList('filter');
        $segment->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'custom_field_test',
                'object'     => 'lead',
                'type'       => 'text',
                'operator'   => '!empty',
                'properties' => [
                    'filter' => null,
                ],
            ],
        ]);
        $this->em->persist($segment);
        $this->em->flush();

        $this->client->request('GET', '/api/segments?search=filters_field:custom_field_test');
        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Segment filter', (string) $clientResponse->getContent());
    }

    private function createCustomField(): LeadField
    {
        $field = new LeadField();
        $field->setType('text');
        $field->setObject('lead');
        $field->setGroup('core');
        $field->setLabel('Test field');
        $field->setAlias('custom_field_test');
        $field->setCharLengthLimit(64);

        return $field;
    }
}
