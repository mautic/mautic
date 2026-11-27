<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Event\EntityPreSave;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManagerMock        = $this->createMock(EntityManager::class);
        $this->userHelperMock           = $this->createMock(UserHelper::class);
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

        $this->entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->createStub(FormRepository::class));

        $formModel = new class($this->entityManagerMock, $this->createStub(CorePermissions::class), $this->createStub(EventDispatcherInterface::class), $this->createStub(UrlGeneratorInterface::class), $this->createStub(Translator::class), $this->userHelperMock, $this->createStub(LoggerInterface::class), $this->createStub(CoreParametersHelper::class)) extends FormModel {
            /**
             * @var array<string>
             */
            private array $actions = [];

            protected function dispatchEvent(string $action, object &$entity, bool $isNew = false, ?Event $event = null): ?Event
            {
                $this->actions[] = $action;

                return $event;
            }

            protected function dispatchBatchEvent(string $action, array $entitiesBatchParams, ?Event $event = null): ?Event
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

    #[AllowMockObjectsWithoutExpectations]
    public function testPostSaveEventKeepsChangesReadInPreSaveEvent(): void
    {
        $asset = new Asset();
        $asset->setTitle('New title');

        $userHelper = $this->createStub(UserHelper::class);
        $userHelper->method('getUser')->willReturn(new User());
        $entityManager = $this->createStub(EntityManager::class);
        $entityManager->method('getRepository')->willReturn($this->createStub(FormRepository::class));

        $postSaveChanges = null;
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('hasListeners')->willReturn(true);
        $eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$postSaveChanges): object {
                if ($event instanceof EntityPreSave) {
                    $event->getChanges();
                }

                if ($event instanceof EntityPostSave) {
                    $postSaveChanges = $event->getChanges();
                }

                return $event;
            });

        $formModel = new class($entityManager, $this->createStub(CorePermissions::class), $eventDispatcher, $this->createStub(UrlGeneratorInterface::class), $this->createStub(Translator::class), $userHelper, $this->createStub(LoggerInterface::class), $this->createStub(CoreParametersHelper::class)) extends FormModel {
        };
        $formModel->saveEntity($asset);

        $this->assertIsArray($postSaveChanges);
        $this->assertSame([null, 'New title'], $postSaveChanges['title']);
    }
}
