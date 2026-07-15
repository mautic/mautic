<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Model\TrackableModel;

final class TrackableRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private TrackableModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = self::getContainer()->get('mautic.page.model.trackable');
    }

    public function testGetCount(): void
    {
        $redirectOne = $this->createRedirect('http://example.com/a/b');
        $redirectTwo = $this->createRedirect('http://example.com/a/b/c');

        $leadA = $this->createLead('john.a@doe.com');
        $leadB = $this->createLead('john.b@doe.com');
        $leadC = $this->createLead('john.c@doe.com');
        $leadD = $this->createLead('john.d@doe.com');

        $this->createTrackable('channel-a', 1, $redirectOne);
        $this->createHit($leadA, $redirectOne, 'channel-a', 1);
        $this->createHit($leadB, $redirectOne, 'channel-a', 1);
        $this->createTrackable('channel-a', 2, $redirectTwo);
        $this->createHit($leadC, $redirectOne, 'channel-a', 2);

        $this->createTrackable('channel-b', 2, $redirectOne);
        $this->createHit($leadA, $redirectOne, 'channel-b', 2);
        $this->createHit($leadC, $redirectOne, 'channel-b', 2);

        $this->createTrackable('channel-b', 3, $redirectTwo);
        $this->createHit($leadB, $redirectOne, 'channel-b', 3);
        $this->createHit($leadD, $redirectOne, 'channel-b', 3);

        $segment = $this->createSegment();
        $this->addContactsToSegment($segment, [$leadA, $leadB, $leadC]);

        $this->assertSame('2', $this->model->getRepository()->getCount('channel-a', [1, 2], null));

        $this->assertEmpty($this->model->getRepository()->getCount('channel-a', [2], [$segment->getId()]));

        $count = $this->model->getRepository()->getCount('channel-b', [1, 2, 3], [$segment->getId()]);

        $this->assertNotEmpty($count);
        $this->assertArrayHasKey($segment->getId(), $count);
        $this->assertSame(2, (int) $count[$segment->getId()]);
    }

    private function createTrackable(string $channel, int $channelId, Redirect $redirect): void
    {
        $trackable = new Trackable();
        $trackable->setChannel($channel)
            ->setChannelId($channelId)
            ->setRedirect($redirect);

        $this->model->getRepository()->saveEntity($trackable);
    }

    private function createRedirect(string $url): Redirect
    {
        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId();

        $this->model->getRepository()->saveEntity($redirect);

        return $redirect;
    }

    private function createHit(Lead $lead, Redirect $redirect, string $source, int $sourceId): void
    {
        $hit = new Hit();
        $hit->setLead($lead);
        $hit->setDateHit(new \DateTime());
        $hit->setTrackingId('random');
        $hit->setCode(200);
        $hit->setSource($source);
        $hit->setSourceId($sourceId);
        $hit->setRedirect($redirect);

        $this->model->getRepository()->saveEntity($hit);
    }

    private function createSegment(): LeadList
    {
        $segment = new LeadList();
        $segment->setName('test');
        $segment->setPublicName('test');
        $segment->setAlias('test-alias');

        $this->model->getRepository()->saveEntity($segment);

        return $segment;
    }

    private function createLead(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);

        $this->model->getRepository()->saveEntity($lead);

        return $lead;
    }

    /**
     * @param Lead[] $leads
     */
    private function addContactsToSegment(LeadList $segment, array $leads): void
    {
        $contacts = [];
        foreach ($leads as $lead) {
            $listLead = new ListLead();
            $listLead->setLead($lead);
            $listLead->setList($segment);
            $listLead->setDateAdded(new \DateTime());
            $contacts[] = $listLead;
        }
        $this->model->getRepository()->saveEntities($contacts);
    }
}
