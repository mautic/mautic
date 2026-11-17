<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\ContactExtras;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\ReportBuilder;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\ValueNormalizer;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\HubspotApiFake;
use MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\HubspotTestTrait;
use PHPUnit\Framework\TestCase;

final class ReportBuilderTest extends TestCase
{
    use HubspotTestTrait;

    private HubspotApiFake $api;

    private ContactExtras $contactExtras;

    private ReportBuilder $reportBuilder;

    protected function setUp(): void
    {
        $this->api           = new HubspotApiFake();
        $client              = $this->api->client($this->config());
        $this->contactExtras = new ContactExtras();
        $this->reportBuilder = new ReportBuilder($client, $this->fieldRepository($client), new ValueNormalizer(), $this->contactExtras);
    }

    public function testReportsTheRequestedPropertiesOfModifiedContacts(): void
    {
        $this->api->queue('POST /crm/v3/objects/contacts/search', [
            'results' => [[
                'id'         => '51',
                'properties' => ['email' => 'john@example.com', 'firstname' => 'John', 'interests' => 'golf;tennis', 'createdate' => '2026-01-01T00:00:00Z', 'lifecyclestage' => 'lead', 'associatedcompanyid' => '900'],
                'updatedAt'  => '2026-11-12T08:00:00Z',
            ]],
        ]);

        $report = $this->build(1, ['email', 'firstname', 'interests']);

        $contact = $this->findContact($report, '51');
        $this->assertInstanceOf(ReportObjectDAO::class, $contact);
        $this->assertSame('2026-11-12T08:00:00+00:00', $contact->getChangeDateTime()?->format(DATE_ATOM));
        $this->assertSame(['email', 'firstname', 'interests'], array_keys($contact->getFields()));
        $this->assertSame('golf|tennis', $contact->getField('interests')->getValue()->getNormalizedValue());

        // The lifecycle stage and company are asked for and kept for after the sync
        $this->assertSame(['email', 'firstname', 'interests', 'lifecyclestage', 'associatedcompanyid', 'lastmodifieddate'], $this->api->sentTo('POST /crm/v3/objects/contacts/search')[0]['properties']);
        $this->assertSame(['stage' => 'lead', 'companyId' => '900'], $this->contactExtras->get('51'));
    }

    public function testFollowsTheCursorUntilTheLastPage(): void
    {
        $this->api
            ->queue('POST /crm/v3/objects/contacts/search', ['results' => [$this->contact('1')], 'paging' => ['next' => ['after' => '100']]])
            ->queue('POST /crm/v3/objects/contacts/search', ['results' => [$this->contact('2')]]);

        $this->assertInstanceOf(ReportObjectDAO::class, $this->findContact($this->build(1), '1'));
        $this->assertInstanceOf(ReportObjectDAO::class, $this->findContact($this->build(2), '2'));
        // finished: no more requests
        $this->assertFalse($this->build(3)->shouldSync());

        $this->assertSame('100', $this->api->sentTo('POST /crm/v3/objects/contacts/search')[1]['after']);
    }

    public function testStartsANewSearchBeforeTheSearchResultLimit(): void
    {
        $this->api
            ->queue('POST /crm/v3/objects/contacts/search', ['results' => [$this->contact('1', '2026-11-10T10:00:00Z')], 'paging' => ['next' => ['after' => '9950']]])
            ->queue('POST /crm/v3/objects/contacts/search', ['results' => []]);

        $this->build(1);
        $this->build(2);

        $second = $this->api->sentTo('POST /crm/v3/objects/contacts/search')[1];
        $this->assertArrayNotHasKey('after', $second);
        $this->assertSame((string) (strtotime('2026-11-10T10:00:00Z') * 1000), $second['filterGroups'][0]['filters'][0]['value']);
    }

    public function testReadsSpecificRecordsOnceWhenGivenTheirIds(): void
    {
        $this->api->queue('POST /crm/v3/objects/contacts/batch/read', ['results' => [$this->contact('77')]]);

        $ids = new ObjectIdsDAO();
        $ids->addObjectId(HubspotClient::CONTACTS, '77');
        $options = new InputOptionsDAO(['integration' => HubspotIntegration::NAME, 'integration-object-id' => $ids]);

        $this->assertInstanceOf(ReportObjectDAO::class, $this->findContact($this->build(1, ['email'], $options), '77'));
        $this->assertFalse($this->build(2, ['email'], $options)->shouldSync());
        $this->assertSame([['id' => '77']], $this->api->sentTo('POST /crm/v3/objects/contacts/batch/read')[0]['inputs']);
    }

    /**
     * @param string[] $fields
     */
    private function build(int $iteration, array $fields = ['email'], ?InputOptionsDAO $options = null): ReportDAO
    {
        $request = new RequestDAO(HubspotIntegration::NAME, $iteration, $options ?? new InputOptionsDAO(['integration' => HubspotIntegration::NAME]));
        $object  = new RequestObjectDAO(HubspotClient::CONTACTS, new \DateTimeImmutable('2026-11-01T00:00:00Z'), new \DateTimeImmutable('2026-11-12T12:00:00Z'));
        foreach ($fields as $field) {
            $object->addField($field);
        }
        $request->addObject($object);

        return $this->reportBuilder->build($request);
    }

    private function findContact(ReportDAO $report, string $id): ?ReportObjectDAO
    {
        foreach ($report->getObjects(HubspotClient::CONTACTS) as $object) {
            if ($id === (string) $object->getObjectId()) {
                return $object;
            }
        }

        return null;
    }

    /**
     * @return array{id: string, properties: array{email: string}, updatedAt: string}
     */
    private function contact(string $id, string $updatedAt = '2026-11-12T08:00:00Z'): array
    {
        return ['id' => $id, 'properties' => ['email' => $id.'@example.com'], 'updatedAt' => $updatedAt];
    }
}
