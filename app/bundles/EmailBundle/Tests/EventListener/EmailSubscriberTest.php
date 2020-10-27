<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\QueueEmailEvent;
use Mautic\EmailBundle\EventListener\EmailSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\Translation\TranslatorInterface;

class EmailSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $ipLookupHelper;

    private $auditLogModel;

    private $emailModel;

    private $translator;

    private $em;

    private $subscriber;

    protected function setup()
    {
        parent::setUp();

        $this->ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel  = $this->createMock(AuditLogModel::class);
        $this->emailModel     = $this->createMock(EmailModel::class);
        $this->translator     = $this->createMock(TranslatorInterface::class);
        $this->em             = $this->createMock(EntityManager::class);
        $this->subscriber     = new EmailSubscriber($this->ipLookupHelper, $this->auditLogModel, $this->emailModel, $this->translator, $this->em);
    }

    public function testOnEmailResendWhenShouldTryAgain()
    {
        $mockSwiftMessage = $this->getMockBuilder(\Swift_Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSwiftMessage->leadIdHash = 'idhash';

        $queueEmailEvent = new QueueEmailEvent($mockSwiftMessage);

        $stat = new Stat();
        $stat->setRetryCount(2);

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus')
            ->willReturn($stat);

        $this->subscriber->onEmailResend($queueEmailEvent);
        $this->assertTrue($queueEmailEvent->shouldTryAgain());
    }

    public function testOnEmailResendWhenShouldNotTryAgain()
    {
        $mockSwiftMessage = $this->getMockBuilder(\Swift_Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSwiftMessage->leadIdHash = 'idhash';

        $mockSwiftMessage->expects($this->once())
            ->method('getSubject')
            ->willReturn('Subject');

        $queueEmailEvent = new QueueEmailEvent($mockSwiftMessage);

        $stat = new Stat();
        $stat->setRetryCount(3);

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus')
            ->willReturn($stat);

        $this->subscriber->onEmailResend($queueEmailEvent);
        $this->assertFalse($queueEmailEvent->shouldTryAgain());
    }
}
