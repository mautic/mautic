<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testCancelScheduledCampaignEventAction()
    {
        $contact  = $this->createContact();
        $campaign = $this->createCampaign();
        $this->addContactToCampaign($contact, $campaign);

        $output = $this->runCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        Assert::assertStringContainsString('1 total event was scheduled', $output);

        $payload = [
            'action'    => 'campaign:cancelScheduledCampaignEvent',
            'eventId'   => $campaign->getEvents()[0]->getId(),
            'contactId' => $contact->getId(),
        ];

        $this->client->request(Request::METHOD_POST, '/s/ajax', $payload, [], $this->createAjaxHeaders());

        // Ensure we'll fetch fresh data from the database and not from entity manager.
        $this->em->clear();

        /** @var LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->em->getRepository(LeadEventLog::class);

        /** @var LeadEventLog $log */
        $log = $leadEventLogRepository->findOneBy(['lead' => $contact, 'campaign' => $campaign]);

        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertSame('{"success":1}', $this->client->getResponse()->getContent());
        Assert::assertFalse($log->getIsScheduled());
    }

    private function createContact(): Lead
    {
        $contact = new Lead();

        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    private function addContactToCampaign(Lead $contact, Campaign $campaign): void
    {
        $ref = new CampaignLead();
        $ref->setCampaign($campaign);
        $ref->setLead($contact);
        $ref->setDateAdded(new \DateTime());

        $this->em->persist($ref);
        $this->em->flush();
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign A');
        $campaign->setIsPublished(true);

        $this->em->persist($campaign);
        $this->em->flush();

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Adjust contact points');
        $event->setType('lead.changepoints');
        $event->setEventType('action');
        $event->setTriggerInterval(1);
        $event->setTriggerIntervalUnit('d');
        $event->setTriggerMode('interval');
        $event->setProperties(
            [
                'canvasSettings' => [
                    'droppedX' => '1080',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'interval',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'leadsource',
                'properties'                 => ['points' => '5'],
                'type'                       => 'lead.changepoints',
                'eventType'                  => 'action',
                'anchorEventType'            => 'source',
                'campaignId'                 => $campaign->getId(),
                'buttons'                    => ['save' => ''],
                'points'                     => 5,
            ]
        );

        $this->em->persist($event);
        $this->em->flush();

        $campaign->addEvent(0, $event);
        $campaign->setCanvasSettings(
            [
                'nodes' => [
                    [
                        'id'        => $event->getId(),
                        'positionX' => '1080',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '1180',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        return $campaign;
    }
}
