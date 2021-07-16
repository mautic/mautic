<?php
declare(strict_types=1);
namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Symfony\Component\HttpFoundation\Response;

class ListControllerTest extends MauticMysqlTestCase
{
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
            $segmentContacts->setManuallyAdded(0);
            $segmentContacts->setManuallyRemoved(0);
            $this->em->persist($segmentContacts);
        }
        $this->em->flush();

        return $segment;
    }

    public function testSegmentViewGraph(): void
    {
        $payload = ['daterange' => [
            'date_from' => 'Feb 1, 2021',
            'date_to'   => 'Jun 4, 2021',
        ],
        ];
        $contacts = $this->createContacts();
        $segment  = $this->addContactsToSegment($contacts, 'SegmentTest');

        foreach ($contacts as $contact) {
            $leadEventLog = new LeadEventLog();
            $leadEventLog->setLead($contact);
            $leadEventLog->setAction('added');
            $leadEventLog->setObject('segment');
            $leadEventLog->setObjectId($segment->getId());
            $leadEventLog->setBundle('lead');
            $leadEventLog->setDateAdded(new \DateTime('2021-02-24 11:56:38'));
            $this->em->persist($leadEventLog);
        }
        $this->em->flush();

        $crawler  = $this->client->request('POST', sprintf('/s/segments/view/%d', $segment->getId()), $payload);
        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $canvas        = $crawler->filter('canvas');
        $daysLeadAdded = json_decode($canvas->text(), true)['datasets'][0]['data'][0];
        // 2 Leads were added in February
        self::assertSame(2, $daysLeadAdded);
    }
}