<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\QueueEmailEvent;
use Mautic\EmailBundle\EventListener\EmailSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Translation\TranslatorInterface;

final class EmailSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var MockObject|AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var MockObject|EmailModel
     */
    private $emailModel;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|EntityManager
     */
    private $em;

    /**
     * @var MockObject|\Swift_Message
     */
    private $mockSwiftMessage;

    /**
     * @var EmailSubscriber
     */
    private $subscriber;

    protected function setup(): void
    {
        parent::setUp();

        $this->ipLookupHelper   = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel    = $this->createMock(AuditLogModel::class);
        $this->emailModel       = $this->createMock(EmailModel::class);
        $this->translator       = $this->createMock(TranslatorInterface::class);
        $this->em               = $this->createMock(EntityManager::class);
        $this->mockSwiftMessage = $this->createMock(\Swift_Message::class);
        $this->subscriber       = new EmailSubscriber($this->ipLookupHelper, $this->auditLogModel, $this->emailModel, $this->translator, $this->em);
    }

    public function testOnEmailResendWhenShouldTryAgain(): void
    {
        $this->mockSwiftMessage->leadIdHash = 'idhash';

        $queueEmailEvent = new QueueEmailEvent($this->mockSwiftMessage);

        $stat = new Stat();
        $stat->setRetryCount(2);

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus')
            ->willReturn($stat);

        $this->subscriber->onEmailResend($queueEmailEvent);
        $this->assertTrue($queueEmailEvent->shouldTryAgain());
    }

    public function testOnEmailResendWhenShouldNotTryAgain(): void
    {
        $this->mockSwiftMessage->leadIdHash = 'idhash';

        $this->mockSwiftMessage->expects($this->once())
            ->method('getSubject')
            ->willReturn('Subject');

        $queueEmailEvent = new QueueEmailEvent($this->mockSwiftMessage);

        $stat = new Stat();
        $stat->setRetryCount(3);

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus')
            ->willReturn($stat);

        $this->subscriber->onEmailResend($queueEmailEvent);
        $this->assertFalse($queueEmailEvent->shouldTryAgain());
    }
}
