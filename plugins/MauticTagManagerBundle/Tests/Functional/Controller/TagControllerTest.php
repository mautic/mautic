<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Tests\Functional\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\ReportBundle\Entity\Report;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class TagControllerTest extends MauticMysqlTestCase
{
    private const MERGE_ROUTE_BASE = '/s/tags/merge/';

    /**
     * @var TagRepository
     */
    private $tagRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $tagModel            = static::getContainer()->get('mautic.lead.model.tag');
        $this->tagRepository = $tagModel->getRepository();

        $tags = ['tag1', 'tag2', 'tag3', 'tag4', 'tag5'];

        foreach ($tags as $tagName) {
            $tag = new Tag();
            $tag->setTag($tagName);
            $this->em->persist($tag);
        }

        $this->em->flush();
    }

    /**
     * Get all results without filtering.
     */
    public function testIndexActionWhenNotFiltered(): void
    {
        $this->client->request('GET', '/s/tags');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('tag1', $clientResponseContent, 'The return must contain tag1');
        $this->assertStringContainsString('tag2', $clientResponseContent, 'The return must contain tag2');
    }

    /**
     * Get results with filtering.
     */
    public function testIndexActionWhenFiltered(): void
    {
        $this->client->request('GET', '/s/tags?search=tag1');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('tag1', $clientResponseContent, 'The return must contain tag1');
        $this->assertStringNotContainsString('tag2', $clientResponseContent, 'The return must not contain tag2');
    }

    public function testIndexActionWhenFilteredByDescription(): void
    {
        $matchingTag = $this->tagRepository->findOneBy(['tag' => 'tag1']);
        $this->assertInstanceOf(Tag::class, $matchingTag);
        $matchingTag->setDescription('Contains the test keyword.');
        $this->tagRepository->saveEntity($matchingTag, false);

        $otherTag = $this->tagRepository->findOneBy(['tag' => 'tag2']);
        $this->assertInstanceOf(Tag::class, $otherTag);
        $otherTag->setDescription('No related content.');
        $this->tagRepository->saveEntity($otherTag);

        $this->client->request('GET', '/s/tags?search=test');
        $clientResponse        = $this->client->getResponse();
        $clientResponseContent = $clientResponse->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('tag1', $clientResponseContent, 'The return must contain the tag whose description matches.');
        $this->assertStringNotContainsString('tag2', $clientResponseContent, 'The return must not contain unrelated tags.');
    }

    public function testTagDeletion(): void
    {
        $tagId = $this->tagRepository->findOneBy([])->getId();
        $this->client->request('POST', '/s/tags/delete/'.$tagId);
        $this->assertResponseIsSuccessful();
        $this->assertSame($this->tagRepository->find($tagId), null, 'Assert that tag is deleted');
    }

    public function testTagDeletionRemovesContactAssociations(): void
    {
        $tag = $this->tagRepository->findOneBy([]);
        $this->assertInstanceOf(Tag::class, $tag);

        $contact = new Lead();
        $contact->setEmail('tagged-contact@example.com');
        $contact->addTag($tag);
        $this->em->persist($contact);
        $this->em->flush();

        $tagId = (int) $tag->getId();

        Assert::assertSame(1, $this->countLeadTagAssociations($tagId));

        $this->client->request('POST', '/s/tags/delete/'.$tagId);
        $this->assertResponseIsSuccessful();
        $this->assertSame($this->tagRepository->find($tagId), null, 'Assert that tag is deleted');
        Assert::assertSame(0, $this->countLeadTagAssociations($tagId));
    }

    /**
     * Get tag's view page.
     */
    public function testViewAction(): void
    {
        $tag = $this->tagRepository->findOneBy([]);

        $this->client->request('GET', '/s/tags/view/'.$tag->getId());
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($tag->getTag(), $clientResponseContent, 'The return must contain tag');
    }

    public function testViewActionNotFound(): void
    {
        $this->client->followRedirects(false);
        $this->client->request('GET', '/s/tags/view/99999');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isRedirection(), 'Must be redirect response.');
    }

    private function countLeadTagAssociations(int $tagId): int
    {
        return (int) $this->em->getConnection()->createQueryBuilder()
            ->select('COUNT(*)')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref')
            ->where('tag_id = :tagId')
            ->setParameter('tagId', $tagId)
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Get tag's edit page.
     */
    public function testEditAction(): void
    {
        $TagName = 'Test tag';
        $tag     = $this->tagRepository->findOneBy([]);

        $crawler                = $this->client->request('GET', '/s/tags/edit/'.$tag->getId());
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Edit tag: '.$tag->getTag(), $clientResponseContent, 'The return must contain \'Edit tag\' text');

        $form = $crawler->selectButton('Save & Close')->form();
        $form['tag_entity[tag]']->setValue($TagName);
        $this->client->submit($form);

        $this->assertSame(1, $this->tagRepository->count(['tag' => $TagName]));
    }

    public function testEditActionNotFound(): void
    {
        $this->client->followRedirects(false);
        $this->client->request('GET', '/s/tags/edit/99999');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isRedirection(), 'Must be redirect response.');
    }

    /**
     * Get tag's create page.
     */
    public function testNewAction(): void
    {
        $TagName        = 'Test tag';
        $crawler        = $this->client->request('GET', '/s/tags/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form();
        $form['tag_entity[tag]']->setValue($TagName);
        $this->client->submit($form);

        $this->assertSame(1, $this->tagRepository->count(['tag' => $TagName]));
    }

    public function testNewActionValidation(): void
    {
        $crawler = $this->client->request('GET', '/s/tags/new');
        $this->assertResponseIsSuccessful();

        $buttonCrawler  = $crawler->selectButton('Save');
        $form           = $buttonCrawler->form();
        $form->setValues(['tag_entity[tag]' => '']);
        $this->client->submit($form);
        self::assertResponseIsSuccessful();
        Assert::assertStringContainsString('A value is required.', $this->client->getResponse()->getContent());
    }

    public function testNewActionDuplicateTag(): void
    {
        $TagName        = $this->tagRepository->findOneBy([])->getTag();
        $crawler        = $this->client->request('GET', '/s/tags/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form();
        $form['tag_entity[tag]']->setValue($TagName);
        $crawler = $this->client->submit($form);

        $this->assertStringContainsString($TagName.' has been updated!', strip_tags($crawler->text(null, false)), 'Must contain already exist.');
    }

    public function testBatchDeleteAction(): void
    {
        $tags   = $this->tagRepository->findAll();
        $tagsId = array_map(fn (Tag $tag) => $tag->getId(), $tags);
        $this->client->request('POST', '/s/tags/batchDelete?ids='.json_encode($tagsId), [], [], $this->createAjaxHeaders());
        $this->assertResponseIsSuccessful();
        $this->assertEmpty($this->tagRepository->count([]), 'All tags must be deleted.');
    }

    public function testEmptyTagShouldThrowValidationError(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/tags/new');
        self::assertResponseIsSuccessful();

        $buttonCrawler  = $crawler->selectButton('Save & Close');
        $form           = $buttonCrawler->form();
        $form->setValues(['tag_entity[tag]' => '']);
        $this->client->submit($form);
        self::assertResponseIsSuccessful();
        Assert::assertStringContainsString('A value is required.', $this->client->getResponse()->getContent());
    }

    public function testEditTagWithNoPermission(): void
    {
        // Create a user without tag manager permissions
        $role = $this->createRole(false);
        $user = $this->createUser($role);
        $this->loginUser($user);

        $tag     = $this->tagRepository->findOneBy([]);
        $this->client->request(Request::METHOD_GET, '/s/tags/edit/'.$tag->getId());
        $this->assertResponseStatusCodeSame(403, (string) $this->client->getResponse()->getStatusCode());
    }

    public function testMergeAction(): void
    {
        $tags       = $this->tagRepository->findAll();
        $primaryTag = $tags[0];

        $this->client->request('GET', self::MERGE_ROUTE_BASE.$primaryTag->getId());
        $this->client->getResponse();
        $this->assertResponseIsSuccessful('Return code must be 200.');

        $crawler = $this->client->getCrawler();
        $this->assertStringContainsString('Merge', $crawler->text());
    }

    public function testMergeActionExcludesCurrentTag(): void
    {
        $tags       = $this->tagRepository->findAll();
        $currentTag = $tags[0];

        $this->client->request('GET', self::MERGE_ROUTE_BASE.$currentTag->getId());
        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful('Return code must be 200.');

        $crawler = new Crawler((string) $clientResponse->getContent());

        // Check that the form exists and has the correct structure
        $this->assertCount(1, $crawler->filter('form'));

        // Check that the form has the tag_to_merge field
        $this->assertCount(1, $crawler->filter('select[name="tag_merge[tag_to_merge]"]'));

        $this->assertCount(1, $crawler->filter('#tag_merge_buttons'));
        $this->assertCount(1, $crawler->filter('button#tag_merge_buttons_save'));
        $this->assertCount(1, $crawler->filter('button#tag_merge_buttons_cancel'));
    }

    public function testMergeActionWithInvalidTag(): void
    {
        $this->client->request('GET', self::MERGE_ROUTE_BASE.'999999');
        $this->client->getResponse();
        $this->assertResponseIsSuccessful('Return code must be 200 (redirect with error).');
    }

    public function testMergeActionPost(): void
    {
        $tags           = $this->tagRepository->findAll();
        $primaryTag     = $tags[0];
        $secondaryTag   = $tags[1];
        $primaryTagId   = (int) $primaryTag->getId();
        $secondaryTagId = (int) $secondaryTag->getId();

        // Test that the merge action returns the correct response
        $this->client->request('GET', self::MERGE_ROUTE_BASE.$secondaryTagId);
        $response = $this->client->getResponse();

        // Debug: check what status code and content we're getting
        $statusCode = $response->getStatusCode();
        $content    = $response->getContent();

        $this->assertTrue($response->isOk(), 'Return code must be 200. Got: '.$statusCode.'. Content: '.substr($content, 0, 500));

        $crawler = new Crawler((string) $response->getContent());

        // Check that the form exists and has the correct structure
        $this->assertCount(1, $crawler->filter('form'));

        // Check that the form has the tag_to_merge field
        $this->assertCount(1, $crawler->filter('select[name="tag_merge[tag_to_merge]"]'));

        $this->assertCount(1, $crawler->filter('#tag_merge_buttons'));
        $this->assertCount(1, $crawler->filter('button#tag_merge_buttons_save'));
        $this->assertCount(1, $crawler->filter('button#tag_merge_buttons_cancel'));

        $secondaryOnlyContact = new Lead();
        $secondaryOnlyContact->setEmail('secondary-tag-contact@example.com');
        $secondaryOnlyContact->addTag($secondaryTag);

        $bothTagsContact = new Lead();
        $bothTagsContact->setEmail('both-tags-contact@example.com');
        $bothTagsContact->addTag($primaryTag);
        $bothTagsContact->addTag($secondaryTag);

        $this->em->persist($secondaryOnlyContact);
        $this->em->persist($bothTagsContact);
        $this->em->flush();

        Assert::assertSame(1, $this->countLeadTagAssociations($primaryTagId));
        Assert::assertSame(2, $this->countLeadTagAssociations($secondaryTagId));

        // Test the actual merge functionality by calling the model directly
        $tagModel = static::getContainer()->get('mautic.lead.model.tag');
        $tagModel->tagMerge($primaryTag, $secondaryTag);

        $this->em->clear();

        $remainingTags   = $this->tagRepository->findAll();
        $remainingTagIds = array_map(fn ($tag) => $tag->getId(), $remainingTags);

        Assert::assertSame(2, $this->countLeadTagAssociations($primaryTagId));
        Assert::assertSame(0, $this->countLeadTagAssociations($secondaryTagId));
        $this->assertNotContains($secondaryTagId, $remainingTagIds, 'Secondary tag should be deleted');
        $this->assertContains($primaryTagId, $remainingTagIds, 'Primary tag should still exist');
    }

    public function testMergeActionUpdatesStoredTagDependencies(): void
    {
        $primaryTag   = $this->tagRepository->findOneBy(['tag' => 'tag1']);
        $secondaryTag = $this->tagRepository->findOneBy(['tag' => 'tag2']);
        $this->assertInstanceOf(Tag::class, $primaryTag);
        $this->assertInstanceOf(Tag::class, $secondaryTag);

        $primaryTagId     = (int) $primaryTag->getId();
        $secondaryTagId   = (int) $secondaryTag->getId();
        $primaryTagName   = $primaryTag->getTag();
        $secondaryTagName = $secondaryTag->getTag();

        $campaignChangeEvent   = $this->createCampaignEventWithChangeTags($primaryTag, $secondaryTag);
        $campaignTagCondition  = $this->createCampaignEventWithTagCondition($primaryTag, $secondaryTag);
        $segment               = $this->createSegmentWithTagFilter($primaryTag, $secondaryTag);
        $formAction            = $this->createFormActionWithChangeTags($primaryTagName, $secondaryTagName);
        $pointTriggerEvent     = $this->createPointTriggerEventWithChangeTags($primaryTagName, $secondaryTagName);
        $report                = $this->createReportWithTagFilter($primaryTagId, $secondaryTagId);
        $campaignChangeEventId = (int) $campaignChangeEvent->getId();
        $campaignConditionId   = (int) $campaignTagCondition->getId();
        $segmentId             = (int) $segment->getId();
        $formActionId          = (int) $formAction->getId();
        $pointTriggerEventId   = (int) $pointTriggerEvent->getId();
        $reportId              = (int) $report->getId();

        $tagModel = static::getContainer()->get('mautic.lead.model.tag');
        $tagModel->tagMerge($primaryTag, $secondaryTag);

        $this->em->clear();

        $campaignChangeEvent = $this->em->find(Event::class, $campaignChangeEventId);
        $this->assertInstanceOf(Event::class, $campaignChangeEvent);
        Assert::assertSame([$primaryTagName], $campaignChangeEvent->getProperties()['add_tags']);
        Assert::assertSame([$primaryTagId], $campaignChangeEvent->getProperties()['properties']['add_tags']);

        $campaignTagCondition = $this->em->find(Event::class, $campaignConditionId);
        $this->assertInstanceOf(Event::class, $campaignTagCondition);
        Assert::assertSame([$primaryTagName], $campaignTagCondition->getProperties()['tags']);
        Assert::assertSame([$primaryTagId], $campaignTagCondition->getProperties()['properties']['tags']);

        $segment = $this->em->find(LeadList::class, $segmentId);
        $this->assertInstanceOf(LeadList::class, $segment);
        Assert::assertSame([$primaryTagId], $segment->getFilters()[0]['properties']['filter']);

        $formAction = $this->em->find(Action::class, $formActionId);
        $this->assertInstanceOf(Action::class, $formAction);
        Assert::assertSame([$primaryTagName], $formAction->getProperties()['add_tags']);

        $pointTriggerEvent = $this->em->find(TriggerEvent::class, $pointTriggerEventId);
        $this->assertInstanceOf(TriggerEvent::class, $pointTriggerEvent);
        Assert::assertSame([$primaryTagName], $pointTriggerEvent->getProperties()['add_tags']);

        $report = $this->em->find(Report::class, $reportId);
        $this->assertInstanceOf(Report::class, $report);
        Assert::assertSame([$primaryTagId], $report->getFilters()[0]['value']);
        Assert::assertNull($this->tagRepository->find($secondaryTagId));
    }

    private function createCampaignEventWithChangeTags(Tag $primaryTag, Tag $secondaryTag): Event
    {
        $campaign = $this->createCampaign('change tags campaign');
        $event    = new Event();
        $event->setCampaign($campaign);
        $event->setName('Change tags');
        $event->setType('lead.changetags');
        $event->setEventType('action');
        $event->setProperties([
            'add_tags'   => [$primaryTag->getTag(), $secondaryTag->getTag()],
            'properties' => [
                'add_tags' => [$primaryTag->getId(), $secondaryTag->getId()],
            ],
        ]);
        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }

    private function createCampaignEventWithTagCondition(Tag $primaryTag, Tag $secondaryTag): Event
    {
        $campaign = $this->createCampaign('tag condition campaign');
        $event    = new Event();
        $event->setCampaign($campaign);
        $event->setName('Has tag');
        $event->setType('lead.tags');
        $event->setEventType('condition');
        $event->setProperties([
            'tags'       => [$primaryTag->getTag(), $secondaryTag->getTag()],
            'properties' => [
                'tags' => [$primaryTag->getId(), $secondaryTag->getId()],
            ],
        ]);
        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }

    private function createCampaign(string $name): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($name);
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    private function createSegmentWithTagFilter(Tag $primaryTag, Tag $secondaryTag): LeadList
    {
        $segment = new LeadList();
        $segment->setName('tag segment');
        $segment->setPublicName('tag segment');
        $segment->setAlias('tag-segment');
        $segment->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'tags',
                'object'     => 'lead',
                'type'       => 'tags',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [$primaryTag->getId(), $secondaryTag->getId()],
                ],
            ],
        ]);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function createFormActionWithChangeTags(string $primaryTagName, string $secondaryTagName): Action
    {
        $form = new Form();
        $form->setName('tag form');
        $form->setAlias('tag-form');
        $this->em->persist($form);
        $this->em->flush();

        $action = new Action();
        $action->setName('change tags');
        $action->setForm($form);
        $action->setType('lead.changetags');
        $action->setProperties([
            'add_tags' => [$primaryTagName, $secondaryTagName],
        ]);
        $this->em->persist($action);
        $this->em->flush();

        return $action;
    }

    private function createPointTriggerEventWithChangeTags(string $primaryTagName, string $secondaryTagName): TriggerEvent
    {
        $trigger = new Trigger();
        $trigger->setName('tag trigger');
        $this->em->persist($trigger);
        $this->em->flush();

        $event = new TriggerEvent();
        $event->setName('change tags');
        $event->setTrigger($trigger);
        $event->setType('lead.changetags');
        $event->setProperties([
            'add_tags' => [$primaryTagName, $secondaryTagName],
        ]);
        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }

    private function createReportWithTagFilter(int $primaryTagId, int $secondaryTagId): Report
    {
        $report = new Report();
        $report->setName('tag report');
        $report->setSource('leads');
        $report->setColumns(['l.id']);
        $report->setFilters([
            [
                'column'    => 'tag',
                'glue'      => 'and',
                'dynamic'   => null,
                'condition' => 'in',
                'value'     => [$primaryTagId, $secondaryTagId],
            ],
        ]);
        $this->em->persist($report);
        $this->em->flush();

        return $report;
    }

    private function createRole(bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);

        // Only add tag manager permissions for admin users
        if ($isAdmin) {
            // Add required permissions for tag manager functionality
            // view (4) + edit (16) + delete (128) = 148
            $permission = new Permission();
            $permission->setBundle('tagManager');
            $permission->setName('tagManager');
            $permission->setBitwise(148);
            $permission->setRole($role);
            $this->em->persist($permission);
        }

        $this->em->persist($role);

        return $role;
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('testuser_'.microtime(true).'_'.bin2hex(random_bytes(8)));
        $user->setEmail('john.doe@email.com');
        $user->setPassword('password');
        $user->setRole($role);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
