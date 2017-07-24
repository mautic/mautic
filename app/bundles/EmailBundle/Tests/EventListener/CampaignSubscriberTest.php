<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Test\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\EventListener\CampaignSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;

class CampaignSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testTransformToUserIdsWithDifferentOwnerId()
    {
        $subscriber = $this->initSubscriber();
        $users      = $subscriber->transformToUserIds([4, 6], 5);
        $expected   = [
            ['id' => 4],
            ['id' => 6],
            ['id' => 5],
        ];

        $this->assertEquals($expected, $users);
    }

    public function testTransformToUserIdsWithSameOwnerId()
    {
        $subscriber = $this->initSubscriber();
        $users      = $subscriber->transformToUserIds([4, 6], 4);
        $expected   = [
            ['id' => 4],
            ['id' => 6],
        ];

        $this->assertEquals($expected, $users);
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithWrongEventType()
    {
        $subscriber = $this->initSubscriber();
        $args       = [
            'lead'  => 64,
            'event' => [
                'type'       => 'email.send',
                'properties' => [
                    'useremail' => [
                        'email' => 33,
                    ],
                ],
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];
        $event = new CampaignExecutionEvent($args, false);
        $subscriber->onCampaignTriggerActionSendEmailToUser($event);

        $this->assertFalse($event->getResult());
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithEmailNotFound()
    {
        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue(null));

        $mockEventModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMessageQueueModel = $this->getMockBuilder(MessageQueueModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new CampaignSubscriber($mockLeadModel, $mockEmailModel, $mockEventModel, $mockMessageQueueModel);
        $args       = [
            'lead'  => 64,
            'event' => [
                'type'       => 'email.send.to.user',
                'properties' => [
                    'useremail' => [
                        'email' => 33,
                    ],
                ],
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];
        $event    = new CampaignExecutionEvent($args, false);
        $expected = [
            'failed' => 1,
            'reason' => 'Email not found or published',
        ];

        $subscriber->onCampaignTriggerActionSendEmailToUser($event);

        $this->assertEquals($expected, $event->getResult());
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithEmailUnpublished()
    {
        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $email = new Email();
        $email->setIsPublished(false);

        $mockEmailModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($email));

        $mockEventModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMessageQueueModel = $this->getMockBuilder(MessageQueueModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new CampaignSubscriber($mockLeadModel, $mockEmailModel, $mockEventModel, $mockMessageQueueModel);

        $args = [
            'lead'  => 64,
            'event' => [
                'type'       => 'email.send.to.user',
                'properties' => [
                    'useremail' => [
                        'email' => 33,
                    ],
                ],
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];
        $event    = new CampaignExecutionEvent($args, false);
        $expected = [
            'failed' => 1,
            'reason' => 'Email not found or published',
        ];

        $subscriber->onCampaignTriggerActionSendEmailToUser($event);

        $this->assertEquals($expected, $event->getResult());
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithSendingTheEmail()
    {
        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $email = new Email();
        $email->setIsPublished(true);

        $mockEmailModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($email));

        $mockEmailModel
            ->expects($this->once())
            ->method('sendEmailToUser')
            ->will($this->returnCallback(function ($email, $users, $leadCredentials, $tokens, $assetAttachments, $saveStat, $to, $cc, $bcc) {
                $expectedUsers = [
                    ['id' => 6],
                    ['id' => 7],
                    ['id' => 10], // owner ID
                ];
                \PHPUnit_Framework_Assert::assertTrue($email instanceof Email);
                \PHPUnit_Framework_Assert::assertEquals($expectedUsers, $users);
                \PHPUnit_Framework_Assert::assertTrue($saveStat);
                \PHPUnit_Framework_Assert::assertEquals(['hello@there.com', 'bob@bobek.cz'], $to);
                \PHPUnit_Framework_Assert::assertEquals([], $cc);
                \PHPUnit_Framework_Assert::assertEquals(['hidden@translation.in'], $bcc);
            }));

        $mockEventModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMessageQueueModel = $this->getMockBuilder(MessageQueueModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new CampaignSubscriber($mockLeadModel, $mockEmailModel, $mockEventModel, $mockMessageQueueModel);

        $mockUser = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockUser->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(10));

        $lead = new Lead();
        $lead->setOwner($mockUser);

        $args = [
            'lead'  => $lead,
            'event' => [
                'type'       => 'email.send.to.user',
                'properties' => [
                    'useremail' => [
                        'email' => 33,
                    ],
                    'user_id'  => [6, 7],
                    'to_owner' => true,
                    'to'       => 'hello@there.com, bob@bobek.cz',
                    'bcc'      => 'hidden@translation.in',
                ],
            ],
            'eventDetails' => [
            ],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];
        $event    = new CampaignExecutionEvent($args, false);
        $expected = [
            'failed' => 1,
            'reason' => 'Email not found or published',
        ];

        $subscriber->onCampaignTriggerActionSendEmailToUser($event);

        $this->assertTrue($event->getResult());
    }

    protected function initSubscriber()
    {
        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEventModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMessageQueueModel = $this->getMockBuilder(MessageQueueModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new CampaignSubscriber($mockLeadModel, $mockEmailModel, $mockEventModel, $mockMessageQueueModel);
    }
}
