<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Tests\Functional\Stats;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\HttpFoundation\Response;

final class TagDependenciesTest extends MauticMysqlTestCase
{
    public function testTagUsageInCampaigns(): void
    {
        $tag = $this->createTag('TagA');

        $campaign  = $this->createCampaignWithChangeTag($tag);
        $campaign2 = $this->createCampaignWithTagCondition($tag);

        $this->client->request('GET', "/s/tags/view/{$tag->getId()}");
        $clientResponse = $this->client->getResponse();
        $content        = $clientResponse->getContent();
        $searchIds      = join(',', [$campaign->getId(), $campaign2->getId()]);
        $this->assertStringContainsString("href=\"/s/campaigns?search=ids:{$searchIds}\"", $content);
    }

    public function testTagUsageInSegments(): void
    {
        $tag = $this->createTag('TagA');

        $segmentWithTag = $this->createSegment('tags', [
            [
                'glue'       => 'and',
                'field'      => 'tags',
                'object'     => 'lead',
                'type'       => 'tags',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [
                        $tag->getId(),
                    ],
                ],
            ],
        ]);

        $this->createSegment('other');

        $this->client->request('GET', "/s/tags/view/{$tag->getId()}");
        $clientResponse = $this->client->getResponse();
        $content        = $clientResponse->getContent();
        $searchIds      = join(',', [$segmentWithTag->getId()]);
        $this->assertStringContainsString("href=\"/s/segments?search=ids:{$searchIds}\"", $content);
    }

    public function testTagUsageInForms(): void
    {
        $tag = $this->createTag('TagA');

        $form = $this->createForm('form-with-tag-action');
        $this->createFormActionChangeTags($form, $tag->getTag());

        $this->client->request('GET', "/s/tags/view/{$tag->getId()}");
        $clientResponse = $this->client->getResponse();
        $content        = $clientResponse->getContent();
        $searchIds      = join(',', [$form->getId()]);
        $this->assertStringContainsString("href=\"/s/forms?search=ids:{$searchIds}\"", $content);
    }

    public function testTagUsageInPointTriggers(): void
    {
        $tag = $this->createTag('TagA');

        $pointActionIsSent = $this->createPointTriggerWithChangeTagsEvent($tag);

        $this->client->request('GET', "/s/tags/view/{$tag->getId()}");
        $clientResponse = $this->client->getResponse();
        $content        = $clientResponse->getContent();
        $searchIds      = join(',', [$pointActionIsSent->getId()]);
        $this->assertStringContainsString("href=\"/s/points/triggers?search=ids:{$searchIds}\"", $content);
    }

    public function testTagUsageInReports(): void
    {
        $tag         = $this->createTag('TagA');
        $report      = $this->createReportWithTag($tag->getId());
        $this->client->request('GET', "/s/tags/view/{$tag->getId()}");
        $clientResponse = $this->client->getResponse();
        $content        = $clientResponse->getContent();
        $searchIds      = join(',', [$report->getId()]);
        $this->assertStringContainsString("href=\"/s/reports?search=ids:{$searchIds}\"", $content);
    }

    public function testTagUsageInReportsEmpty(): void
    {
        $tag         = $this->createTag('TagA');
        $this->createReportWithTagEmpty();
        $this->client->request('GET', "/s/tags/view/{$tag->getId()}");
        $clientResponse = $this->client->getResponse();
        // check that the page loads without errors
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    private function createTag(string $tagName): Tag
    {
        $tag = new Tag();
        $tag->setTag($tagName);
        $this->em->persist($tag);
        $this->em->flush();

        return $tag;
    }

    /**
     * Creates campaign with change tag action.
     *
     * Campaign diagram:
     * -------------------
     * -  Start segment  -
     * -------------------
     *         |
     * -------------------
     * -   Change tag    -
     * -------------------
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignWithChangeTag(Tag $tag): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test Update contact');

        $this->em->persist($campaign);
        $this->em->flush();

        $event1 = new Event();
        $event1->setCampaign($campaign);
        $event1->setName('Add tag');
        $event1->setType('lead.changetags');
        $event1->setEventType('action');
        $event1->setTriggerMode('immediate');
        $event1->setOrder(1);
        $event1->setProperties(
            [
                'canvasSettings' => [
                    'droppedX' => '549',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'leadsource',
                'properties'                 => [
                    'add_tags'    => [$tag->getId()],
                    'remove_tags' => [],
                ],
                'type'            => 'lead.changetags',
                'eventType'       => 'action',
                'anchorEventType' => 'source',
                'campaignId'      => 'mautic_ce6c7dddf8444e579d741c0125f18b33a5d49b45',
                '_token'          => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'         => [
                    'save' => '',
                ],
                'add_tags'    => [$tag->getTag()],
                'remove_tags' => [],
            ]
        );

        $this->em->persist($event1);
        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes'       => [
                    [
                        'id'        => $event1->getId(),
                        'positionX' => '549',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '796',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event1->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /**
     * Creates campaign with has tag condition.
     *
     * Campaign diagram:
     * -------------------
     * -  Start segment  -
     * -------------------
     *         |
     * -------------------
     * -   Has tag?      -
     * -------------------
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignWithTagCondition(Tag $tag): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test contact tags');

        $this->em->persist($campaign);
        $this->em->flush();

        $event1 = new Event();
        $event1->setCampaign($campaign);
        $event1->setName('Contact tags');
        $event1->setType('lead.tags');
        $event1->setEventType('condition');
        $event1->setTriggerMode('immediate');
        $event1->setOrder(1);
        $event1->setProperties(
            [
                'canvasSettings' => [
                    'droppedX' => '549',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'leadsource',
                'properties'                 => [
                    'tags' => [$tag->getId()],
                ],
                'type'            => 'lead.tags',
                'eventType'       => 'condition',
                'anchorEventType' => 'source',
                'campaignId'      => 'mautic_ce6c7dddf8444e579d741c0125f18b33a5d49b45',
                '_token'          => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'         => [
                    'save' => '',
                ],
                'tags' => [$tag->getTag()],
            ]
        );

        $this->em->persist($event1);
        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes'       => [
                    [
                        'id'        => $event1->getId(),
                        'positionX' => '549',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '796',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event1->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /**
     * @param array<int, array<string, mixed>> $filters
     */
    private function createSegment(string $alias, array $filters = []): LeadList
    {
        $segment = new LeadList();
        $segment->setName($alias);
        $segment->setPublicName($alias);
        $segment->setAlias($alias);
        $segment->setFilters($filters);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function createForm(string $alias): Form
    {
        $form = new Form();
        $form->setName($alias);
        $form->setAlias($alias);
        $this->em->persist($form);
        $this->em->flush();

        return $form;
    }

    private function createFormActionChangeTags(Form $form, string $tagName): Action
    {
        $action = new Action();
        $action->setName('change tags');
        $action->setForm($form);
        $action->setType('lead.changetags');
        $action->setProperties([
            'add_tags'    => [$tagName],
            'remove_tags' => [],
        ]);
        $this->em->persist($action);
        $this->em->flush();

        return $action;
    }

    private function createPointTriggerWithChangeTagsEvent(Tag $tag): Trigger
    {
        $pointTrigger = new Trigger();
        $pointTrigger->setName('trigger');
        $this->em->persist($pointTrigger);
        $this->em->flush();

        $triggerEvent = new TriggerEvent();
        $triggerEvent->setTrigger($pointTrigger);
        $triggerEvent->setName('event');
        $triggerEvent->setType('lead.changetags');
        $triggerEvent->setProperties(
            [
                'add_tags'    => [$tag->getTag()],
                'remove_tags' => [],
            ]
        );
        $this->em->persist($triggerEvent);
        $this->em->flush();

        return $pointTrigger;
    }

    private function createReportWithTag(int $tagId): Report
    {
        $report = new Report();
        $report->setName('Contact report');
        $report->setSource('leads');
        $report->setColumns([
            'l.id',
        ]);
        $report->setFilters([
            [
                'column'    => 'tag',
                'glue'      => 'and',
                'dynamic'   => null,
                'condition' => 'in',
                'value'     => [$tagId],
            ],
        ]);
        $this->em->persist($report);
        $this->em->flush();

        return $report;
    }

    private function createReportWithTagEmpty(): Report
    {
        $report = new Report();
        $report->setName('Contact report');
        $report->setSource('leads');
        $report->setColumns([
            'l.id',
        ]);
        $report->setFilters([
            [
                'column'    => 'tag',
                'glue'      => 'and',
                'dynamic'   => null,
                'condition' => 'empty',
                'value'     => null,
            ],
        ]);
        $this->em->persist($report);
        $this->em->flush();

        return $report;
    }
}
