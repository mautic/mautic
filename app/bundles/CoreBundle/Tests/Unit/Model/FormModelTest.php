<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class FormModelTest extends TestCase
{
    /**
     * @var MockObject&EntityManager
     */
    private MockObject $entityManagerMock;

    /**
     * @var MockObject&UserHelper
     */
    private MockObject $userHelperMock;

    /**
     * @var MockObject&CorePermissions
     */
    private MockObject $corePermissionsMock;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $dispatcherMock;

    /**
     * @var MockObject&UrlGeneratorInterface
     */
    private MockObject $routerMock;

    /**
     * @var MockObject&Translator
     */
    private MockObject $translatorMock;

    /**
     * @var MockObject&LoggerInterface
     */
    private MockObject $loggerMock;

    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $coreParametersHelperMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManagerMock        = $this->createMock(EntityManager::class);
        $this->corePermissionsMock      = $this->createMock(CorePermissions::class);
        $this->dispatcherMock           = $this->createMock(EventDispatcherInterface::class);
        $this->routerMock               = $this->createMock(UrlGeneratorInterface::class);
        $this->translatorMock           = $this->createMock(Translator::class);
        $this->userHelperMock           = $this->createMock(UserHelper::class);
        $this->loggerMock               = $this->createMock(LoggerInterface::class);
        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
    }

    public function testSaveEntities(): void
    {
        $leads = [];
        for ($x = 0; $x < 30; ++$x) {
            $lead = new Lead();
            $lead->setEmail(sprintf('test%s@test.cz', $x));
            $leads[] = $lead;
        }

        $this->entityManagerMock->expects($this->exactly(2))
            ->method('flush');

        $this->userHelperMock->expects($this->exactly(60))
            ->method('getUser')
            ->willReturn(new User());

        $this->entityManagerMock->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->createMock(FormRepository::class));

        $formModel = new class($this->entityManagerMock, $this->corePermissionsMock, $this->dispatcherMock, $this->routerMock, $this->translatorMock, $this->userHelperMock, $this->loggerMock, $this->coreParametersHelperMock) extends FormModel {
            /**
             * @var array<string>
             */
            private array $actions = [];

            protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
            {
                $this->actions[] = $action;

                return $event;
            }

            protected function dispatchBatchEvent(string $action, array &$entitiesBatchParams, Event $event = null): ?Event
            {
                $this->actions[] = $action;

                return $event;
            }

            /**
             * @return string[]
             */
            public function getActionsSent(): array
            {
                return $this->actions;
            }
        };
        $formModel->saveEntities($leads);
        $actionsSent   = $formModel->getActionsSent();
        $countDispatch = 0;
        foreach ($actionsSent as $action) {
            if ($countDispatch < 30) {
                $this->assertSame('pre_save', $action);
            } elseif (30 === $countDispatch) {
                $this->assertSame('pre_batch_save', $action);
            } elseif ($countDispatch < 61) {
                $this->assertSame('post_save', $action);
            } elseif (61 === $countDispatch) {
                $this->assertSame('post_batch_save', $action);
            }
            ++$countDispatch;
        }
    }
}
