<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Tests\Functional\Stats;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\Tag;

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

    private function createTag(string $tagName): Tag
    {
        $tag = new Tag();
        $tag->setTag($tagName);
        $this->em->persist($tag);
        $this->em->flush();

        return $tag;
    }

    /**
     * Creates campaign with email sent action.
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
     * Creates campaign with email sent action.
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
}
