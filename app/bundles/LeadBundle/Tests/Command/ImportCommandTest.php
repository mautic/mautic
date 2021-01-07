<?php

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\LeadBundle\Command\ImportCommand;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Security\UserTokenSetter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\TranslatorInterface;

class ImportCommandTest extends TestCase
{
    public function testExecute()
    {
        $importCommand =  new class() extends ImportCommand {
            public function getExecute(InputInterface $input, OutputInterface $output)
            {
                return $this->execute($input, $output);
            }
        };
        // Translator
        $translatorMock = $this->createMock(TranslatorInterface::class);
        // Import entity
        $importMock = $this->createMock(Import::class);
        $importMock->expects($this->at(0))
            ->method('getCreatedBy')
            ->willReturn(42);
        // Import Model Mock
        $importModelMock = $this->createMock(ImportModel::class);
        $importModelMock->expects($this->at(0))
            ->method('getEntity')
            ->with(42)
            ->willReturn($importMock);
        // User Token Setter
        $user               = new User();
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $userRepositoryMock->expects($this->once())
            ->method('getEntity')
            ->with(42)
            ->willReturn($user);
        $tokenStorageMock   = $this->createMock(TokenStorage::class);
        $tokenStorageMock->expects($this->once())
            ->method('setToken');
        $userTokenSetter  = new UserTokenSetter($userRepositoryMock, $tokenStorageMock);
        // Container
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->at(0))
            ->method('get')
            ->with('translator')
            ->willReturn($translatorMock);
        $containerMock->expects($this->at(1))
            ->method('get')
            ->with('mautic.lead.model.import')
            ->willReturn($importModelMock);
        $containerMock->expects($this->at(2))
            ->method('get')
            ->with('mautic.security.user_token_setter')
            ->willReturn($userTokenSetter);
        $importCommand->setContainer($containerMock);
        // InputInterface
        $inputInterfaceMock = $this->createMock(InputInterface::class);
        $inputInterfaceMock->expects($this->at(0))
            ->method('getOption')
            ->with('id')
            ->willReturn(42);
        $inputInterfaceMock->expects($this->at(1))
            ->method('getOption')
            ->with('limit')
            ->willReturn(10);
        // OutputInterface
        $outputInterfaceMock = $this->createMock(OutputInterface::class);
        // Start test
        $this->assertSame(0, $importCommand->getExecute($inputInterfaceMock, $outputInterfaceMock));
    }
}
