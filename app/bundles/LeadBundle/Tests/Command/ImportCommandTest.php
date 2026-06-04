<?php

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\LeadBundle\Command\ImportCommand;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Security\UserTokenSetter;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportCommandTest extends TestCase
{
    public function testExecuteFailsIfModifiedByIsNotSet(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturnCallback(fn ($id) => $id);
        $importMock       = $this->createMock(Import::class);
        $importModelMock  = $this->createMock(ImportModel::class);
        $loggerMock       = $this->createMock(Logger::class);
        $notificationMock = $this->createMock(NotificationModel::class);
        $userModelMock    = $this->createMock(UserModel::class);
        $tokenStorageMock = $this->createMock(TokenStorage::class);
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
        $inputInterfaceMock  = $this->createMock(InputInterface::class);
        $outputInterfaceMock = $this->createMock(OutputInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Import does not have "modifiedBy" property set.');
        $importCommand->getExecute($inputInterfaceMock, $outputInterfaceMock);
    }

    public function testExecute(): void
    {
        // Translator
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturnCallback(fn ($id) => $id);

        // Import entity
        $importMock = $this->createMock(Import::class);
        $importMock->expects($this->once())
            ->method('getModifiedBy')
            ->willReturn(42);
        $importMock->method('getProcessedRows')->willReturn(1);
        $importMock->method('getInsertedCount')->willReturn(1);
        $importMock->method('getUpdatedCount')->willReturn(0);
        $importMock->method('getIgnoredCount')->willReturn(0);

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

        $loggerMock       = $this->createMock(Logger::class);
        $notificationMock = $this->createMock(NotificationModel::class);
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
        $inputInterfaceMock->expects($matcher)->method('getOption')->willReturnCallback(function (...$parameters) use ($matcher) {
            if (1 === $matcher->numberOfInvocations()) {
                $this->assertSame('id', $parameters[0]);

                return 42;
            }
            if (2 === $matcher->numberOfInvocations()) {
                $this->assertSame('limit', $parameters[0]);

                return 10;
            }
        });

        // OutputInterface
        $outputInterfaceMock = $this->createMock(OutputInterface::class);
        // Start test
        $this->assertSame(0, $importCommand->getExecute($inputInterfaceMock, $outputInterfaceMock));
    }

    public function testExecuteAddsNotificationOnFailure(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturnCallback(fn ($id) => $id);

        $importMock = $this->createMock(Import::class);
        $importMock->expects($this->once())
            ->method('getModifiedBy')
            ->willReturn(42);
        $importMock->method('getStatusInfo')->willReturn('fail');
        $importMock->method('getProcessedRows')->willReturn(1);
        $importMock->method('getInsertedCount')->willReturn(0);
        $importMock->method('getUpdatedCount')->willReturn(0);
        $importMock->method('getIgnoredCount')->willReturn(1);

        $importModelMock = $this->createMock(ImportModel::class);
        $importModelMock->expects($this->once())
            ->method('getEntity')
            ->with(42)
            ->willReturn($importMock);
        $importModelMock->expects($this->once())
            ->method('beginImport')
            ->willThrowException(new \Mautic\LeadBundle\Exception\ImportFailedException('fail'));

        $user               = new User();
        $userModelMock      = $this->createMock(UserModel::class);
        $userModelMock->expects($this->once())
            ->method('getEntity')
            ->with(42)
            ->willReturn($user);
        $tokenStorageMock   = $this->createMock(TokenStorage::class);
        $tokenStorageMock->expects($this->once())->method('setToken');
        $userTokenSetter    = new UserTokenSetter($userModelMock, $tokenStorageMock);

        $loggerMock       = $this->createMock(Logger::class);
        $notificationMock = $this->createMock(NotificationModel::class);
        $notificationMock->expects($this->once())->method('addNotification');

        $importCommand = new class($translatorMock, $importModelMock, new ProcessSignalService(), $userTokenSetter, $loggerMock, $notificationMock) extends ImportCommand {
            public function getExecute(InputInterface $input, OutputInterface $output): int
            {
                return $this->execute($input, $output);
            }
        };

        $inputInterfaceMock = $this->createMock(InputInterface::class);
        $inputInterfaceMock->method('getOption')->willReturnMap([
            ['id', 42],
            ['limit', 10],
        ]);

        $outputInterfaceMock = $this->createMock(OutputInterface::class);

        $this->assertSame(1, $importCommand->getExecute($inputInterfaceMock, $outputInterfaceMock));
    }
}
