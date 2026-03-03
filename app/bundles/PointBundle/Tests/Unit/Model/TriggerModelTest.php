<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Form\Type\EmailToUserType;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Entity\TriggerEventRepository;
use Mautic\PointBundle\Model\TriggerEventModel;
use Mautic\PointBundle\Model\TriggerModel;
use Mautic\PointBundle\PointEvents;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TriggerModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IpLookupHelper&MockObject
     */
    private MockObject $ipLookupHelper;

    /**
     * @var LeadModel&MockObject
     */
    private MockObject $leadModel;

    /**
     * @var TriggerEventModel&MockObject
     */
    private MockObject $triggerEventModel;

    /**
     * @var EventDispatcherInterface&MockObject
     */
    private MockObject $dispatcher;

    /**
     * @var TranslatorInterface&MockObject
     */
    private MockObject $translator;

    /**
     * @var EntityManager&MockObject
     */
    private MockObject $entityManager;

    /**
     * @var TriggerEventRepository&MockObject
     */
    private MockObject $triggerEventRepository;

    private TriggerModel $triggerModel;

    /**
     * @var ContactTracker&MockObject
     */
    private MockObject $contactTracker;

    public function setUp(): void
    {
        parent::setUp();
        $this->ipLookupHelper         = $this->createMock(IpLookupHelper::class);
        $this->leadModel              = $this->createMock(LeadModel::class);
        $this->triggerEventModel      = $this->createMock(TriggerEventModel::class);
        $this->contactTracker         = $this->createMock(ContactTracker::class);
        $this->dispatcher             = $this->createMock(EventDispatcherInterface::class);
        $this->translator             = $this->createMock(Translator::class);
        $this->entityManager          = $this->createMock(EntityManager::class);
        $this->triggerEventRepository = $this->createMock(TriggerEventRepository::class);
        $this->triggerModel           = new TriggerModel(
            $this->ipLookupHelper,
            $this->leadModel,
            $this->triggerEventModel,
            $this->contactTracker,
            $this->entityManager,
            $this->createMock(CorePermissions::class),
            $this->dispatcher,
            $this->createMock(UrlGeneratorInterface::class),
            $this->translator,
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class)
        );

        // reset private property cachedEvents in TriggerModel instance
        $reflectionClass = new \ReflectionClass(TriggerModel::class);
        $property        = $reflectionClass->getProperty('cachedEvents');
        $property->setAccessible(true);
        $property->setValue($this->triggerModel, []);
    }

    public function testTriggerEvent(): void
    {
        $triggerEvent  = new TriggerEvent();
        $contact       = new Lead();
        $dispatchCalls = new \ArrayObject();

        $triggerEvent->setType('email.send_to_user');

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->triggerEventRepository);

        $this->triggerEventRepository->expects($this->once())
            ->method('find')
            ->willReturn($triggerEvent);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event, $eventName) use ($dispatchCalls, $contact, $triggerEvent) {
                $dispatchCalls->append($eventName);

                if (PointEvents::TRIGGER_ON_BUILD === $eventName) {
                    // Emulate a subscriber:
                    $event->addEvent(
                        'email.send_to_user',
                        [
                            'group'           => 'mautic.email.point.trigger',
                            'label'           => 'mautic.email.point.trigger.send_email_to_user',
                            'formType'        => EmailToUserType::class,
                            'formTypeOptions' => ['update_select' => 'pointtriggerevent_properties_useremail_email'],
                            'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList',
                            'eventName'       => EmailEvents::ON_SENT_EMAIL_TO_USER,
                        ]
                    );

                    return $event;
                } elseif (EmailEvents::ON_SENT_EMAIL_TO_USER === $eventName) {
                    Assert::assertSame($contact, $event->getLead());
                    Assert::assertSame($triggerEvent, $event->getTriggerEvent());

                    return $event;
                } else {
                    $this->fail("Unexpected event name: $eventName");
                }
            });

        $this->triggerModel->triggerEvent($triggerEvent->convertToArray(), $contact, true);

        // Assert both expected events were dispatched
        Assert::assertContains(PointEvents::TRIGGER_ON_BUILD, $dispatchCalls);
        Assert::assertContains(EmailEvents::ON_SENT_EMAIL_TO_USER, $dispatchCalls);
        Assert::assertCount(2, $dispatchCalls);
    }
}
