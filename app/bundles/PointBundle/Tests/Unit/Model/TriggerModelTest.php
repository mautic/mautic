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
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Entity\TriggerEventRepository;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Mautic\PointBundle\Model\TriggerEventModel;
use Mautic\PointBundle\Model\TriggerModel;
use Mautic\PointBundle\PointEvents;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TriggerModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IpLookupHelper|MockObject
     */
    private MockObject $ipLookupHelper;

    /**
     * @var LeadModel|MockObject
     */
    private MockObject $leadModel;

    /**
     * @var TriggerEventModel|MockObject
     */
    private MockObject $triggerEventModel;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private MockObject $dispatcher;

    /**
     * @var TranslatorInterface|MockObject
     */
    private MockObject $translator;

    /**
     * @var EntityManager|MockObject
     */
    private MockObject $entityManager;

    /**
     * @var TriggerEventRepository|MockObject
     */
    private MockObject $triggerEventRepository;

    private TriggerModel $triggerModel;

    /**
     * @var ContactTracker
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

        // reset private static property events in TriggerModel
        $reflectionClass = new \ReflectionClass(TriggerModel::class);
        $property        = $reflectionClass->getProperty('events');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    public function testTriggerEvent(): void
    {
        $triggerEvent = new TriggerEvent();
        $contact      = new Lead();

        $triggerEvent->setType('email.send_to_user');

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->triggerEventRepository);

        $this->triggerEventRepository->expects($this->once())
            ->method('find')
            ->willReturn($triggerEvent);
        $matcher = $this->exactly(2);

        $this->dispatcher->expects($matcher)
            ->method('dispatch')->willReturnCallback(function (object $event, string $eventName) use ($matcher, $contact, $triggerEvent) {
                if (1 === $matcher->numberOfInvocations()) {
                    $callback = function (TriggerBuilderEvent $event) {
                        // PHPUNIT calls this callback twice for unknown reason. We need to set it only once.
                        if (array_key_exists('email.send_to_user', $event->getEvents())) {
                            return;
                        }

                        $event->addEvent(
                            'email.send_to_user',
                            [
                                'group'           => 'mautic.email.point.trigger',
                                'label'           => 'mautic.email.point.trigger.send_email_to_user',
                                'formType'        => \Mautic\EmailBundle\Form\Type\EmailToUserType::class,
                                'formTypeOptions' => ['update_select' => 'pointtriggerevent_properties_useremail_email'],
                                'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList',
                                'eventName'       => EmailEvents::ON_SENT_EMAIL_TO_USER,
                            ]
                        );
                    };
                    $callback($event);
                    $this->assertSame(PointEvents::TRIGGER_ON_BUILD, $eventName);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $callback = function (TriggerExecutedEvent $event) use ($contact, $triggerEvent) {
                        $this->assertSame($contact, $event->getLead());
                        $this->assertSame($triggerEvent, $event->getTriggerEvent());
                    };
                    $callback($event);
                    $this->assertSame(EmailEvents::ON_SENT_EMAIL_TO_USER, $eventName);
                }

                return $event;
            });

        $this->triggerModel->triggerEvent($triggerEvent->convertToArray(), $contact, true);
    }
}
