<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use Mautic\CoreBundle\Helper\EmailAddressHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailReply;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Event\EmailReplyEvent;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\EmailBundle\MonitoredEmail\Search\Result;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReplyTest extends \PHPUnit\Framework\TestCase
{
    private EmailAddressHelper $emailAddressHelper;

    private \PHPUnit\Framework\MockObject\MockObject $statRepo;

    private \PHPUnit\Framework\MockObject\MockObject $contactFinder;

    private \PHPUnit\Framework\MockObject\MockObject $leadModel;

    private \PHPUnit\Framework\MockObject\MockObject $dispatcher;

    private \PHPUnit\Framework\MockObject\MockObject $logger;

    private \PHPUnit\Framework\MockObject\MockObject $contactTracker;

    private \Mautic\EmailBundle\MonitoredEmail\Processor\Reply $processor;

    /**
     * @var MockObject&LeadRepository
     */
    private MockObject $leadRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statRepo           = $this->createMock(StatRepository::class);
        $this->contactFinder      = $this->createMock(ContactFinder::class);
        $this->leadModel          = $this->createMock(LeadModel::class);
        $this->dispatcher         = $this->createMock(EventDispatcherInterface::class);
        $this->logger             = $this->createMock(Logger::class);
        $this->contactTracker     = $this->createMock(ContactTracker::class);
        $this->emailAddressHelper = new EmailAddressHelper();
        $this->leadRepository     = $this->createMock(LeadRepository::class);
        $this->leadModel->method('getRepository')->willReturn($this->leadRepository);
        $this->processor          = new Reply(
            $this->statRepo,
            $this->contactFinder,
            $this->leadModel,
            $this->dispatcher,
            $this->logger,
            $this->contactTracker,
            $this->emailAddressHelper
        );
    }

    /**
     * @testdox Test that the message is processed appropriately
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Reply::process
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Search\Result::getStat
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Search\Result::getContacts
     */
    public function testContactIsFoundFromMessageAndDncRecordAdded(): void
    {
        // This tells us that a reply was found and processed
        $this->statRepo->expects($this->once())
            ->method('saveEntity');

        $this->leadRepository->expects(self::atLeastOnce())
            ->method('detachEntity');

        $this->contactFinder->method('findByHash')
            ->willReturnCallback(
                function ($hash) {
                    $stat = new Stat();
                    $stat->setTrackingHash($hash);

                    $lead = new Lead();
                    $lead->setEmail('contact@email.com');
                    $stat->setLead($lead);

                    $email = new Email();
                    $stat->setEmail($email);

                    $result = new Result();
                    $result->setStat($stat);
                    $result->setContacts(
                        [
                            $lead,
                        ]
                    );

                    return $result;
                }
            );

        $message              = new Message();
        $message->fromAddress = 'contact@email.com';
        $message->textHtml    = <<<'BODY'
<img src="http://test.com/email/123abc.gif" />
BODY;

        $this->processor->process($message);
    }

    public function testCreateReplyByHashIfStatNotFound(): void
    {
        $trackingHash = '@Stat#';

        $this->statRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['trackingHash' => $trackingHash])
            ->willReturn(null);

        $this->expectException(EntityNotFoundException::class);

        $this->processor->createReplyByHash($trackingHash, 'api-msg1d');
    }

    public function testCreateReplyByHash(): void
    {
        $trackingHash = '@Stat#';
        $stat         = $this->createMock(Stat::class);
        $contact      = $this->createMock(Lead::class);

        $this->statRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['trackingHash' => $trackingHash])
            ->willReturn($stat);

        $stat->expects($this->once())
            ->method('setIsRead')
            ->with(true);

        $stat->expects($this->once())
            ->method('getDateRead')
            ->willReturn(null);

        $stat->expects($this->once())
            ->method('setDateRead')
            ->with($this->isInstanceOf(\DateTime::class));

        $stat->expects($this->any())
            ->method('getReplies')
            ->willReturn(new ArrayCollection());

        $stat->expects($this->once())
            ->method('addReply')
            ->with($this->callback(function (EmailReply $emailReply) use ($stat) {
                $this->assertSame($stat, $emailReply->getStat());
                $this->assertSame('api-msg1d', $emailReply->getMessageId());

                return true;
            }));

        $this->statRepo->expects($this->once())
            ->method('saveEntity')
            ->with($this->isInstanceOf(Stat::class));

        $stat->expects($this->exactly(2))
            ->method('getLead')
            ->willReturn($contact);

        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(EmailEvents::EMAIL_ON_REPLY)
            ->willReturn(true);

        $this->contactTracker->expects($this->once())
            ->method('setTrackedContact')
            ->with($contact);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EmailReplyEvent::class), EmailEvents::EMAIL_ON_REPLY);

        $this->processor->createReplyByHash($trackingHash, 'api-msg1d');
    }
}
