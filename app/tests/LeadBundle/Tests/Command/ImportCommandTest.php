<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\LeadBundle\Command\ImportCommand;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Security\UserTokenSetter;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class ImportCommandTest extends TestCase
{
    public function testExecuteFailsIfModifiedByIsNotSet(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturnCallback(fn (string $id): string => $id);
        $importMock       = new Import();
        $importModelMock  = $this->createMock(ImportModel::class);
        $loggerMock       = $this->createStub(Logger::class);
        $notificationMock = $this->createStub(NotificationModel::class);
        $userModelMock    = $this->createStub(UserModel::class);
        $tokenStorageMock = $this->createStub(TokenStorage::class);
        $userTokenSetter  = new UserTokenSetter($userModelMock, $tokenStorageMock);

        $importModelMock->expects($this->once())
            ->method('getImportToProcess')
            ->willReturn($importMock);

        $importCommand =  new class($translatorMock, $importModelMock, new ProcessSignalService(), $userTokenSetter, $loggerMock, $notificationMock) extends ImportCommand {
            public function getExecute(InputInterface $input, OutputInterface $output): int
            {
                return $this->execute($input, $output);
            }
        };
        $inputInterfaceMock  = $this->createStub(InputInterface::class);
        $outputInterfaceMock = $this->createStub(OutputInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Import does not have "modifiedBy" property set.');
        $importCommand->getExecute($inputInterfaceMock, $outputInterfaceMock);
    }

    public function testExecute(): void
    {
        // Translator
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturnCallback(fn (string $id): string => $id);

        // Import entity
        $importMock = new Import();
        $importMock->setModifiedBy(42);
        $importMock->setInsertedCount(1);

        // Import Model Mock
        $importModelMock = $this->createMock(ImportModel::class);
        $importModelMock->expects($this->once())
            ->method('getEntity')
            ->with(42)
            ->willReturn($importMock);

        // User Token Setter
        $user               = new User();
        $userModelMock      = $this->createMock(UserModel::class);
        $userModelMock->expects($this->once())
            ->method('getEntity')
            ->with(42)
            ->willReturn($user);
        $tokenStorageMock   = $this->createMock(TokenStorage::class);
        $tokenStorageMock->expects($this->once())
            ->method('setToken');
        $userTokenSetter  = new UserTokenSetter($userModelMock, $tokenStorageMock);

        $loggerMock       = $this->createStub(Logger::class);
        $notificationMock = $this->createStub(NotificationModel::class);
        // No notification expected for successful imports - they're handled in ImportModel

        $importCommand =  new class($translatorMock, $importModelMock, new ProcessSignalService(), $userTokenSetter, $loggerMock, $notificationMock) extends ImportCommand {
            public function getExecute(InputInterface $input, OutputInterface $output): int
            {
                return $this->execute($input, $output);
            }
        };

        // InputInterface
        $inputInterfaceMock = $this->createMock(InputInterface::class);
        $matcher            = $this->exactly(2);
        $inputInterfaceMock->expects($matcher)->method('getOption')->willReturnCallback(function (...$parameters) use ($matcher): int {
            if (1 === $matcher->numberOfInvocations()) {
                $this->assertSame('id', $parameters[0]);

                return 42;
            }
            if (2 === $matcher->numberOfInvocations()) {
                $this->assertSame('limit', $parameters[0]);

                return 10;
            }

            throw new Exception(sprintf('Method not be called for %dth time', $matcher->numberOfInvocations()));
        });

        // OutputInterface
        $outputInterfaceMock = $this->createStub(OutputInterface::class);
        // Start test
        $this->assertSame(0, $importCommand->getExecute($inputInterfaceMock, $outputInterfaceMock));
    }

    public function testExecuteAddsNotificationOnFailure(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturnCallback(fn (string $id): string => $id);

        $importMock = new Import();
        $importMock->setModifiedBy(42);
        $importMock->setStatusInfo('fail');
        $importMock->setIgnoredCount(1);

        $importModelMock = $this->createMock(ImportModel::class);
        $importModelMock->expects($this->once())
            ->method('getEntity')
            ->with(42)
            ->willReturn($importMock);
        $importModelMock->expects($this->once())
            ->method('beginImport')
            ->willThrowException(new ImportFailedException('fail'));

        $user               = new User();
        $userModelMock      = $this->createMock(UserModel::class);
        $userModelMock->expects($this->once())
            ->method('getEntity')
            ->with(42)
            ->willReturn($user);
        $tokenStorageMock   = $this->createMock(TokenStorage::class);
        $tokenStorageMock->expects($this->once())->method('setToken');
        $userTokenSetter    = new UserTokenSetter($userModelMock, $tokenStorageMock);

        $loggerMock       = $this->createStub(Logger::class);
        $notificationMock = $this->createMock(NotificationModel::class);
        $notificationMock->expects($this->once())->method('addNotification');

        $importCommand = new class($translatorMock, $importModelMock, new ProcessSignalService(), $userTokenSetter, $loggerMock, $notificationMock) extends ImportCommand {
            public function getExecute(InputInterface $input, OutputInterface $output): int
            {
                return $this->execute($input, $output);
            }
        };

        $inputInterfaceMock = $this->createMock(InputInterface::class);
        $inputInterfaceMock->expects($this->exactly(2))->method('getOption')->willReturnMap([
            ['id', 42],
            ['limit', 10],
        ]);

        $outputInterfaceMock = $this->createStub(OutputInterface::class);

        $this->assertSame(1, $importCommand->getExecute($inputInterfaceMock, $outputInterfaceMock));
    }
}
