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

use Mautic\EmailBundle\EventListener\EmailToUserSubscriber;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Event\TriggerExecutedEvent;

class EmailToUserSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    private $config = [
        'useremail' => [
            'email' => 33,
        ],
        'user_id'  => [6, 7],
        'to_owner' => true,
        'to'       => 'hello@there.com, bob@bobek.cz',
        'bcc'      => 'hidden@translation.in',
    ];

    public function testOnCampaignTriggerActionSendEmailToUserWithSendingTheEmail()
    {
        $lead = new Lead();

        $mockSendEmailToUser = $this->getMockBuilder(SendEmailToUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new EmailToUserSubscriber($mockSendEmailToUser);

        $mockSendEmailToUser->expects($this->once())
            ->method('sendEmailToUsers')
            ->with($this->config, $lead);

        $mockSendEmailToUser->expects($this->once())
            ->method('sendEmailToUsers')
            ->with($this->config, $lead);

        $triggerEvent = new TriggerEvent();
        $triggerEvent->setProperties($this->config);

        $event = new TriggerExecutedEvent($triggerEvent, $lead);

        $subscriber->onEmailToUser($event);

        $this->assertTrue($event->getResult());
    }

    public function testOnCampaignTriggerActionSendEmailToUserWithError()
    {
        $lead = new Lead();

        $mockSendEmailToUser = $this->getMockBuilder(SendEmailToUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new EmailToUserSubscriber($mockSendEmailToUser);

        $mockSendEmailToUser->expects($this->once())
            ->method('sendEmailToUsers')
            ->with($this->config, $lead);

        $mockSendEmailToUser->expects($this->once())
            ->method('sendEmailToUsers')
            ->with($this->config, $lead)
            ->will($this->throwException(new EmailCouldNotBeSentException()));

        $triggerEvent = new TriggerEvent();
        $triggerEvent->setProperties($this->config);

        $event = new TriggerExecutedEvent($triggerEvent, $lead);

        $subscriber->onEmailToUser($event);

        $this->assertFalse($event->getResult());
    }
}
