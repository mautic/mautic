<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class ContactRequestHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LeadModel
     */
    private $leadModel;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContactTracker
     */
    private $contactTracker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcher
     */
    private $dispatcher;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RequestStack
     */
    private $requestStack;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Logger
     */
    private $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Lead
     */
    private $trackedContact;

    protected function setUp()
    {
        $this->leadModel            = $this->createMock(LeadModel::class);
        $this->contactTracker       = $this->createMock(ContactTracker::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $this->requestStack         = $this->createMock(RequestStack::class);
        $this->logger               = $this->createMock(Logger::class);
        $this->dispatcher           = $this->createMock(EventDispatcher::class);

        $this->trackedContact = $this->createMock(Lead::class);
        $this->trackedContact->method('getId')
            ->willReturn(1);

        $this->trackedContact->method('getIpAddresses')
            ->willReturn(new ArrayCollection());

        $this->contactTracker->method('getContact')
            ->willReturn($this->trackedContact);

        $this->ipLookupHelper->method('getIpAddress')
            ->willReturn(new IpAddress());
    }

    public function testEventDoesNotIdentifyContact()
    {
        $query = [
            'ct' => [
                'lead'    => 2,
                'channel' => [
                    'email' => 1,
                ],
                'stat'    => 'abc123',
            ],
        ];

        $email = $this->createMock(Email::class);
        $email->method('getId')
            ->willReturn(2);

        $stat = new Stat();
        $stat->setEmail($email);

        $this->leadModel->expects($this->never())
            ->method('mergeLeads');

        $this->leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->willReturn([$this->trackedContact, []]);

        $helper = $this->getContactRequestHelper();
        $this->assertEquals($this->trackedContact->getId(), $helper->getContactFromQuery($query)->getId());
    }

    public function testEventIdentifiesContact()
    {
        $query = [
            'ct' => [
                'lead'    => 2,
                'channel' => [
                    'email' => 1,
                ],
                'stat'    => 'abc123',
            ],
        ];

        $contact = new Lead();

        $this->dispatcher->method('dispatch')
            ->willReturnCallback(
                function ($eventName, ContactIdentificationEvent $event) use ($contact) {
                    $event->setIdentifiedContact($contact, 'email');
                }
            );

        $this->leadModel->expects($this->never())
            ->method('mergeLeads');

        $helper       = $this->getContactRequestHelper();
        $foundContact = $helper->getContactFromQuery($query);

        $this->assertTrue($contact === $foundContact);
    }

    public function testLandingPageClickthroughIdentifiesLeadIfEnabled()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('track_by_tracking_url')
            ->willReturn(true);

        $query = [
            'ct' => [
                'lead'    => 2,
                'channel' => [
                    'email' => 1,
                ],
                'stat'    => 'abc123',
            ],
        ];

        $lead = $this->createMock(Lead::class);
        $lead->method('getId')
            ->willReturn(2);
        $lead->method('getIpAddresses')
            ->willReturn(new ArrayCollection());
        $lead->expects($this->once())
            ->method('getEmail')
            ->willReturn('test@test.com');

        $this->leadModel->expects($this->once())
            ->method('getEntity')
            ->with(2)
            ->willReturn($lead);

        $queryWithEmail          = $query;
        $queryWithEmail['email'] = 'test@test.com';

        $this->leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->with($queryWithEmail, $this->trackedContact, true, true)
            ->willReturn([$lead, ['email' => 'test@test.com']]);

        $helper = $this->getContactRequestHelper();
        $this->assertEquals($lead->getId(), $helper->getContactFromQuery($query)->getId());
    }

    public function testLandingPageClickthroughDoesNotIdentifyLeadIfDisabled()
    {
        $this->coreParametersHelper->expects($this->at(0))
            ->method('get')
            ->with('track_by_tracking_url')
            ->willReturn(false);

        $query = [
            'ct' => [
                'lead'    => 2,
                'channel' => [
                    'email' => 1,
                ],
                'stat'    => 'abc123',
            ],
        ];

        $this->leadModel->expects($this->never())
            ->method('getEntity');

        $this->leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->with($query, $this->trackedContact, true, true)
            ->willReturn([$this->trackedContact, []]);

        $helper = $this->getContactRequestHelper();
        $this->assertEquals($this->trackedContact->getId(), $helper->getContactFromQuery($query)->getId());
    }

    /**
     * @return ContactRequestHelper
     */
    private function getContactRequestHelper()
    {
        return new ContactRequestHelper(
            $this->leadModel,
            $this->contactTracker,
            $this->coreParametersHelper,
            $this->ipLookupHelper,
            $this->requestStack,
            $this->logger,
            $this->dispatcher
        );
    }
}
