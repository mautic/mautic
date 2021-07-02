<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\IpLookupHelper;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TriggerModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IpLookupHelper|MockObject
     */
    private $ipLookupHelper;

    /**
     * @var LeadModel|MockObject
     */
    private $leadModel;

    /**
     * @var TriggerEventModel|MockObject
     */
    private $triggerEventModel;

    /**
     * @var MauticFactory|MockObject
     */
    private $mauticFactory;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $dispatcher;

    /**
     * @var TranslatorInterface|MockObject
     */
    private $translator;

    /**
     * @var EntityManager|MockObject
     */
    private $entityManager;

    /**
     * @var TriggerEventRepository|MockObject
     */
    private $triggerEventRepository;

    /**
     * @var TriggerModel
     */
    private $triggerModel;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    public function setUp(): void
    {
        parent::setUp();
        $this->ipLookupHelper         = $this->createMock(IpLookupHelper::class);
        $this->leadModel              = $this->createMock(LeadModel::class);
        $this->triggerEventModel      = $this->createMock(TriggerEventModel::class);
        $this->mauticFactory          = $this->createMock(MauticFactory::class);
        $this->contactTracker         = $this->createMock(ContactTracker::class);
        $this->dispatcher             = $this->createMock(EventDispatcherInterface::class);
        $this->translator             = $this->createMock(TranslatorInterface::class);
        $this->entityManager          = $this->createMock(EntityManager::class);
        $this->triggerEventRepository = $this->createMock(TriggerEventRepository::class);
        $this->triggerModel           = new TriggerModel(
            $this->ipLookupHelper,
            $this->leadModel,
            $this->triggerEventModel,
            $this->mauticFactory,
            $this->contactTracker
        );

        $this->triggerModel->setDispatcher($this->dispatcher);
        $this->triggerModel->setTranslator($this->translator);
        $this->triggerModel->setEntityManager($this->entityManager);
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

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    PointEvents::TRIGGER_ON_BUILD,
                    $this->callback(
                        // Emulate a subscriber:
                        function (TriggerBuilderEvent $event) {
                            // PHPUNIT calls this callback twice for unknown reason. We need to set it only once.
                            if (array_key_exists('email.send_to_user', $event->getEvents())) {
                                return true;
                            }

                            $event->addEvent(
                                'email.send_to_user',
                                [
                                    'group'           => 'mautic.email.point.trigger',
                                    'label'           => 'mautic.email.point.trigger.send_email_to_user',
                                    'formType'        => \Mautic\EmailBundle\Form\Type\EmailToUserType::class,
                                    'formTypeOptions' => ['update_select' => 'pointtriggerevent_properties_email'],
                                    'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList',
                                    'eventName'       => EmailEvents::ON_SENT_EMAIL_TO_USER,
                                ]
                            );

                            return true;
                        }
                    ),
                ],
                // Ensure the event is triggered if the point trigger event has 'eventName' defined instead of 'callback'.
                [
                    EmailEvents::ON_SENT_EMAIL_TO_USER,
                    $this->callback(
                        function (TriggerExecutedEvent $event) use ($contact, $triggerEvent) {
                            $this->assertSame($contact, $event->getLead());
                            $this->assertSame($triggerEvent, $event->getTriggerEvent());

                            return true;
                        }
                    ),
                ]
            );

        $this->triggerModel->triggerEvent($triggerEvent->convertToArray(), $contact, true);
    }
}
