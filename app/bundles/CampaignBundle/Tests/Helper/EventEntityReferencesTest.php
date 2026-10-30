<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Helper;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Helper\EventEntityReferences;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EventEntityReferencesTest extends TestCase
{
    private EventEntityReferences $references;

    protected function setUp(): void
    {
        $this->references = new EventEntityReferences();
    }

    public function testExportedIdsIncludeEverySelectedEntity(): void
    {
        $this->assertSame(
            [Page::ENTITY_NAME => [3, 4]],
            $this->references->getExportedIds('page.pagehit', ['pages' => ['3', '4']])
        );
        $this->assertSame(
            [LeadList::ENTITY_NAME => [1, 2, 5]],
            $this->references->getExportedIds('lead.changelist', ['addToLists' => [1, 2], 'removeFromLists' => [2, 5]])
        );
        $this->assertSame(
            [Email::ENTITY_NAME => [7]],
            $this->references->getExportedIds('email.send.to.user', ['useremail' => ['email' => '7']])
        );
        $this->assertSame([], $this->references->getExportedIds('lead.pageHit', ['page' => '', 'page_url' => 'https://example.com']));
        $this->assertSame([], $this->references->getExportedIds('lead.adddnc', ['channel' => 'email']));
        $this->assertSame([], $this->references->getExportedIds('stage.change', ['stage' => 3]));
    }

    public function testLinkedIdsAreTheNotExportedEntitiesWithUuid(): void
    {
        $this->assertSame([EventEntityReferences::STAGE => [3]], $this->references->getLinkedIds('stage.change', ['stage' => '3']));
        $this->assertSame(
            [Campaign::ENTITY_NAME => [4, 5]],
            $this->references->getLinkedIds('campaign.addremovelead', ['addTo' => [4], 'removeFrom' => ['this', 5]])
        );
        $this->assertSame([], $this->references->getLinkedIds('lead.changeowner', ['owner' => 2]));
        $this->assertSame([], $this->references->getLinkedIds('form.submit', ['forms' => [1]]));
    }

    public function testTagNames(): void
    {
        $this->assertSame(
            ['one', 'two'],
            $this->references->getTagNames('lead.changetags', ['add_tags' => ['one', '7'], 'remove_tags' => ['two', 'one']])
        );
        $this->assertSame([], $this->references->getTagNames('lead.tags', ['tags' => null]));
    }

    public function testFieldAliases(): void
    {
        $this->assertSame(['custom'], $this->references->getFieldAliases('lead.field_value', ['field' => 'custom', 'properties' => ['field' => 'custom']]));
        $this->assertSame(
            ['firstname', 'custom'],
            $this->references->getFieldAliases('lead.updatelead', ['name' => 'Update', 'properties' => ['firstname' => 'A', 'custom' => 'B', 'lastname' => '', 'city' => null]])
        );
        $this->assertSame([], $this->references->getFieldAliases('lead.changetags', ['add_tags' => ['one']]));
    }

    /**
     * @param array<string, mixed> $properties
     * @param array<string, mixed> $expectedProperties
     */
    #[DataProvider('provideEvents')]
    public function testRemapEventPointsTheReferencesToTheImportedEntities(string $type, array $properties, array $expectedProperties): void
    {
        $idMaps = [
            Asset::ENTITY_NAME          => [1 => 101, 2 => 102],
            DynamicContent::ENTITY_NAME => [1 => 201],
            Email::ENTITY_NAME          => [1 => 301],
            Form::ENTITY_NAME           => [1 => 401, 2 => 402],
            Group::ENTITY_NAME          => [1 => 501],
            LeadList::ENTITY_NAME       => [1 => 601, 2 => 602],
            Page::ENTITY_NAME           => [1 => 701, 2 => 702],
        ];

        $event = $this->references->remapEvent(
            ['type' => $type, 'properties' => $properties + ['properties' => $properties]],
            $idMaps
        );

        $this->assertSame($expectedProperties + ['properties' => $expectedProperties], $event['properties']);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, array<string, mixed>}>
     */
    public static function provideEvents(): iterable
    {
        yield 'form submitted' => ['form.submit', ['forms' => ['1', '2']], ['forms' => [401, 402]]];
        yield 'page hit with two pages' => ['page.pagehit', ['pages' => [1, 2], 'url' => ''], ['pages' => [701, 702], 'url' => '']];
        yield 'asset download with two assets' => ['asset.download', ['assets' => [1, 2]], ['assets' => [101, 102]]];
        yield 'request dynamic content' => ['dwc.decision', ['dynamicContent' => '1'], ['dynamicContent' => 201]];
        yield 'visited page' => ['lead.pageHit', ['page' => '2', 'page_url' => ''], ['page' => 702, 'page_url' => '']];
        yield 'contact points' => ['lead.points', ['operator' => 'gt', 'score' => 5, 'group' => '1'], ['operator' => 'gt', 'score' => 5, 'group' => 501]];
        yield 'form field value' => ['form.field_value', ['form' => '2', 'field' => 'email'], ['form' => 402, 'field' => 'email']];
        yield 'adjust contact points' => ['lead.changepoints', ['points' => 5, 'group' => 1], ['points' => 5, 'group' => 501]];
        yield 'send email to user' => ['email.send.to.user', ['useremail' => ['email' => '1'], 'user_id' => [1]], ['useremail' => ['email' => 301], 'user_id' => []]];
        yield 'modify contact segments' => ['lead.changelist', ['addToLists' => ['1'], 'removeFromLists' => ['2']], ['addToLists' => [601], 'removeFromLists' => [602]]];
        yield 'contact segments' => ['lead.segments', ['segments' => [2, 1]], ['segments' => [602, 601]]];
        yield 'send email' => ['email.send', ['email' => 1, 'email_type' => 'marketing'], ['email' => 301, 'email_type' => 'marketing']];
        yield 'no point group selected' => ['lead.points', ['score' => 5, 'group' => ''], ['score' => 5, 'group' => '']];
    }

    public function testRemapEventRemovesReferencesWithoutAnImportedEntity(): void
    {
        $event = $this->references->remapEvent(
            [
                'type'       => 'lead.changelist',
                'properties' => [
                    'addToLists'      => [1, 9],
                    'removeFromLists' => [9],
                    'properties'      => ['addToLists' => [1, 9], 'removeFromLists' => [9]],
                ],
            ],
            [LeadList::ENTITY_NAME => [1 => 601]]
        );

        // The old ID 9 could belong to an unrelated segment here
        $this->assertSame([601], $event['properties']['addToLists']);
        $this->assertSame([], $event['properties']['removeFromLists']);
        $this->assertSame([601], $event['properties']['properties']['addToLists']);
        $this->assertSame([], $event['properties']['properties']['removeFromLists']);

        $event = $this->references->remapEvent(['type' => 'lead.points', 'properties' => ['group' => 9]], []);
        $this->assertNull($event['properties']['group']);
    }

    public function testRemapEventPointsTheChannelToTheImportedEntity(): void
    {
        $event = $this->references->remapEvent(
            ['type' => 'email.send', 'channel' => 'email', 'channel_id' => 1, 'properties' => ['email' => 1]],
            [Email::ENTITY_NAME => [1 => 301]]
        );
        $this->assertSame(301, $event['channel_id']);

        $event = $this->references->remapEvent(
            ['type' => 'dwc.decision', 'channel' => 'dynamicContent', 'channel_id' => 1, 'properties' => ['dynamicContent' => 1]],
            [DynamicContent::ENTITY_NAME => [1 => 201]]
        );
        $this->assertSame(201, $event['channel_id']);

        // The builder stores no channel for a form submit, events created another way can have one
        $event = $this->references->remapEvent(
            ['type' => 'form.submit', 'channel' => 'form', 'channel_id' => 4, 'properties' => ['forms' => [4]]],
            [Form::ENTITY_NAME => [4 => 404]]
        );
        $this->assertSame(404, $event['channel_id']);
        $this->assertSame([404], $event['properties']['forms']);

        $event = $this->references->remapEvent(
            ['type' => 'page.pagehit', 'channel' => 'page', 'channel_id' => 9, 'properties' => ['pages' => [9]]],
            []
        );
        $this->assertNull($event['channel_id']);
    }

    public function testRemapEventFindsLinkedEntitiesAndRemovesTheOnesThatCannotBeFound(): void
    {
        $idMaps = [
            Campaign::ENTITY_NAME            => [1 => 101, 2 => 102],
            EventEntityReferences::STAGE     => [3 => 303],
            EventEntityReferences::MESSAGE   => [4 => 404],
        ];

        $event = $this->references->remapEvent(['type' => 'campaign.addremovelead', 'properties' => ['addTo' => [2, 9], 'removeFrom' => ['this', 1]]], $idMaps);
        $this->assertSame(['addTo' => [102], 'removeFrom' => ['this', 101]], $event['properties']);

        $event = $this->references->remapEvent(['type' => 'stage.change', 'properties' => ['stage' => '3']], $idMaps);
        $this->assertSame(303, $event['properties']['stage']);

        $event = $this->references->remapEvent(['type' => 'lead.stages', 'properties' => ['stages' => [3, 8]]], $idMaps);
        $this->assertSame([303], $event['properties']['stages']);

        $event = $this->references->remapEvent(['type' => 'message.send', 'channel' => 'channel.message', 'channel_id' => 4, 'properties' => ['marketingMessage' => 4]], $idMaps);
        $this->assertSame(404, $event['properties']['marketingMessage']);
        $this->assertSame(404, $event['channel_id']);

        $event = $this->references->remapEvent(['type' => 'sms.send_text_sms', 'channel' => 'sms', 'channel_id' => 5, 'properties' => ['sms' => 5]], $idMaps);
        $this->assertNull($event['properties']['sms']);
        $this->assertNull($event['channel_id']);
    }

    public function testRemapEventRemovesCompaniesAndUsers(): void
    {
        $event = $this->references->remapEvent(['type' => 'lead.changeowner', 'properties' => ['owner' => 2, 'properties' => ['owner' => '2']]], []);
        $this->assertNull($event['properties']['owner']);
        $this->assertNull($event['properties']['properties']['owner']);

        $event = $this->references->remapEvent(['type' => 'lead.addtocompany', 'properties' => ['company' => 5]], []);
        $this->assertNull($event['properties']['company']);

        $event = $this->references->remapEvent(
            ['type' => 'email.send.to.user', 'properties' => ['useremail' => ['email' => 1], 'user_id' => [1, 2], 'to_owner' => true]],
            [Email::ENTITY_NAME => [1 => 301]]
        );
        $this->assertSame(['useremail' => ['email' => 301], 'user_id' => [], 'to_owner' => true], $event['properties']);
    }

    public function testRemapEventKeepsTagNamesAndRemovesTagIds(): void
    {
        $event = $this->references->remapEvent(['type' => 'lead.changetags', 'properties' => ['add_tags' => ['one', '7'], 'remove_tags' => []]], []);
        $this->assertSame(['add_tags' => ['one'], 'remove_tags' => []], $event['properties']);
    }

    public function testRemapEventRenamesChangedFieldAliases(): void
    {
        $aliases = ['custom' => 'custom1'];

        $event = $this->references->remapEvent(['type' => 'lead.field_value', 'properties' => ['field' => 'custom', 'value' => 'a', 'properties' => ['field' => 'custom', 'value' => 'a']]], [], $aliases);
        $this->assertSame('custom1', $event['properties']['field']);
        $this->assertSame('custom1', $event['properties']['properties']['field']);

        $event = $this->references->remapEvent(['type' => 'lead.updatelead', 'properties' => ['properties' => ['firstname' => 'A', 'custom' => 'B']]], [], $aliases);
        $this->assertSame(['firstname' => 'A', 'custom1' => 'B'], $event['properties']['properties']);

        $event = $this->references->remapEvent(['type' => 'lead.field_value', 'properties' => ['field' => 'other']], [], $aliases);
        $this->assertSame('other', $event['properties']['field']);
    }

    public function testRemapEventKeepsEventsWithoutReferences(): void
    {
        $event = [
            'type'       => 'lead.adddnc',
            'channel'    => 'email',
            'channel_id' => 3,
            'properties' => ['channel' => 'email', 'reason' => 3],
        ];

        $this->assertSame($event, $this->references->remapEvent($event, []));
    }

    public function testNamedIdsAreTheCompanies(): void
    {
        $this->assertSame([EventEntityReferences::COMPANY => [5]], $this->references->getNamedIds('lead.addtocompany', ['company' => '5']));
        // Users would need their email, which a shared campaign must not contain
        $this->assertSame([], $this->references->getNamedIds('lead.changeowner', ['owner' => 2]));

        $event = $this->references->remapEvent(['type' => 'lead.addtocompany', 'properties' => ['company' => 5]], [EventEntityReferences::COMPANY => [5 => 12]]);
        $this->assertSame(12, $event['properties']['company']);
    }

    public function testFormActionReferences(): void
    {
        $this->assertSame([Email::ENTITY_NAME => [3]], $this->references->getFormActionExportedIds('email.send.lead', ['email' => '3']));
        $this->assertSame(
            [LeadList::ENTITY_NAME => [1, 2]],
            $this->references->getFormActionExportedIds('lead.changelist', ['addToLists' => [1], 'removeFromLists' => [2]])
        );
        $this->assertSame([Asset::ENTITY_NAME => [4]], $this->references->getFormActionExportedIds('asset.download', ['asset' => 4, 'category' => 6]));
        $this->assertSame([], $this->references->getFormActionExportedIds('form.repost', ['post_url' => 'https://example.com']));
        $this->assertSame([EventEntityReferences::CATEGORY => [6]], $this->references->getFormActionLinkedIds('asset.download', ['asset' => 4, 'category' => 6]));
        $this->assertSame(['one'], $this->references->getFormActionTagNames('lead.changetags', ['add_tags' => ['one', '7'], 'remove_tags' => []]));

        $idMaps = [Email::ENTITY_NAME => [3 => 303], LeadList::ENTITY_NAME => [1 => 101], Group::ENTITY_NAME => [2 => 202], Asset::ENTITY_NAME => [4 => 404]];

        $this->assertSame(['email' => 303], $this->references->remapFormActionProperties('email.send.lead', ['email' => '3'], $idMaps));
        $this->assertSame(
            ['useremail' => ['email' => 303], 'user_id' => []],
            $this->references->remapFormActionProperties('email.send.user', ['useremail' => ['email' => 3], 'user_id' => [1]], $idMaps)
        );
        // The second segment was not imported, so its old ID could belong to an unrelated segment
        $this->assertSame(
            ['addToLists' => [101], 'removeFromLists' => []],
            $this->references->remapFormActionProperties('lead.changelist', ['addToLists' => [1], 'removeFromLists' => [2]], $idMaps)
        );
        $this->assertSame(['points' => 5, 'group' => 202], $this->references->remapFormActionProperties('lead.pointschange', ['points' => 5, 'group' => 2], $idMaps));
        $this->assertSame(['asset' => 404, 'category' => null], $this->references->remapFormActionProperties('asset.download', ['asset' => 4, 'category' => 6], $idMaps));
        $this->assertSame(['subject' => 'Hi'], $this->references->remapFormActionProperties('form.email', ['subject' => 'Hi'], $idMaps));
    }

    public function testSegmentFilterReferences(): void
    {
        $filters = [
            $this->segmentFilter('leadlist', [3, 4]),
            $this->segmentFilter('lead_email_received', ['5']),
            $this->segmentFilter('tags', [6]),
            $this->segmentFilter('stage', 7),
            $this->segmentFilter('email', 'a@example.com'),
        ];

        $this->assertSame([LeadList::ENTITY_NAME => [3, 4], Email::ENTITY_NAME => [5]], $this->references->getSegmentFilterExportedIds($filters));
        $this->assertSame([EventEntityReferences::STAGE => [7]], $this->references->getSegmentFilterLinkedIds($filters));
        $this->assertSame(
            [EventEntityReferences::CATEGORY => [2]],
            $this->references->getSegmentFilterLinkedIds([$this->segmentFilter('globalcategory', [2])])
        );
        $this->assertSame([6], $this->references->getSegmentFilterTagIds($filters));

        $idMaps = [
            LeadList::ENTITY_NAME              => [3 => 103, 4 => 104],
            Email::ENTITY_NAME                 => [5 => 105],
            EventEntityReferences::TAG_ID      => [6 => 106],
            EventEntityReferences::STAGE       => [7 => 107],
        ];

        [$remapped, $complete] = $this->references->remapSegmentFilters($filters, $idMaps);

        $this->assertTrue($complete);
        $this->assertSame([
            $this->segmentFilter('leadlist', [103, 104]),
            $this->segmentFilter('lead_email_received', [105]),
            $this->segmentFilter('tags', [106]),
            $this->segmentFilter('stage', 107),
            $this->segmentFilter('email', 'a@example.com'),
        ], $remapped);
    }

    public function testSegmentFilterWithoutImportedEntitiesIsIncomplete(): void
    {
        [$remapped, $complete] = $this->references->remapSegmentFilters(
            [$this->segmentFilter('leadlist', [3, 4]), $this->segmentFilter('globalcategory', [2])],
            [LeadList::ENTITY_NAME => [3 => 103]]
        );

        $this->assertFalse($complete);
        $this->assertSame([$this->segmentFilter('leadlist', [103]), $this->segmentFilter('globalcategory', [])], $remapped);

        // A filter without a value stays as it is
        [, $complete] = $this->references->remapSegmentFilters([$this->segmentFilter('leadlist', [])], []);
        $this->assertTrue($complete);
    }

    /**
     * A segment filter as the segment returns it, with the value also outside of the properties for older code.
     *
     * @return array<string, mixed>
     */
    private function segmentFilter(string $field, mixed $value): array
    {
        return [
            'glue'       => 'and',
            'field'      => $field,
            'object'     => 'lead',
            'type'       => $field,
            'operator'   => 'in',
            'properties' => ['filter' => $value],
            'filter'     => $value,
        ];
    }
}
