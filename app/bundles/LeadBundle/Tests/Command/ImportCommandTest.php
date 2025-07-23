<?php

namespace Mautic\LeadBundle\Tests\Command;

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
        $translatorMock   = $this->createMock(TranslatorInterface::class);
        $importMock       = $this->createMock(Import::class);
        $importModelMock  = $this->createMock(ImportModel::class);
        $loggerMock       = $this->createMock(Logger::class);
        $userModelMock    = $this->createMock(UserModel::class);
        $tokenStorageMock = $this->createMock(TokenStorage::class);
        $userTokenSetter  = new UserTokenSetter($userModelMock, $tokenStorageMock);

        $importModelMock->expects($this->once())
            ->method('getImportToProcess')
            ->willReturn($importMock);

        $importCommand =  new class($translatorMock, $importModelMock, new ProcessSignalService(), $userTokenSetter, $loggerMock) extends ImportCommand {
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

        // Import entity
        $importMock = $this->createMock(Import::class);
        $importMock->expects($this->once())
            ->method('getModifiedBy')
            ->willReturn(42);

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

        $loggerMock = $this->createMock(Logger::class);

        $importCommand =  new class($translatorMock, $importModelMock, new ProcessSignalService(), $userTokenSetter, $loggerMock) extends ImportCommand {
            public function getExecute(InputInterface $input, OutputInterface $output): int
            {
                return $this->execute($input, $output);
            }
        };

        // InputInterface
        $inputInterfaceMock = $this->createMock(InputInterface::class);
        $inputInterfaceMock->method('getOption')
            ->withConsecutive(['id'], ['limit'])
            ->willReturnOnConsecutiveCalls(42, 10);

        // OutputInterface
        $outputInterfaceMock = $this->createMock(OutputInterface::class);
        // Start test
        $this->assertSame(0, $importCommand->getExecute($inputInterfaceMock, $outputInterfaceMock));
    }
}
