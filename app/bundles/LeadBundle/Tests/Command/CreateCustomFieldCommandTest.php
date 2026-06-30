<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Field\BackgroundService;
use Mautic\LeadBundle\Field\Command\CreateCustomFieldCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateCustomFieldCommandTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\Stub $backgroundServiceMock;

    private \PHPUnit\Framework\MockObject\Stub $translatorMock;

    private \PHPUnit\Framework\MockObject\Stub $leadFieldRepositoryMock;

    private \PHPUnit\Framework\MockObject\Stub $pathsHelperMock;

    private \PHPUnit\Framework\MockObject\Stub $coreParametersHelper;

    protected function setUp(): void
    {
        $this->backgroundServiceMock   = $this->createStub(BackgroundService::class);
        $this->translatorMock          = $this->createStub(TranslatorInterface::class);
        $this->leadFieldRepositoryMock = $this->createStub(LeadFieldRepository::class);
        $this->pathsHelperMock         = $this->createStub(PathsHelper::class);
        $this->coreParametersHelper    = $this->createStub(CoreParametersHelper::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('completeRunMethodProvider')]
    public function testCompleteRunMethodIsCalled(bool $checkRunStatusResult, int $completeRunExpected): void
    {
        $command = $this->getMockBuilder(CreateCustomFieldCommand::class)
            ->setConstructorArgs([
                $this->backgroundServiceMock,
                $this->translatorMock,
                $this->leadFieldRepositoryMock,
                $this->pathsHelperMock,
                $this->coreParametersHelper,
            ])
            ->onlyMethods(['completeRun', 'checkRunStatus'])
            ->getMock();

        $command->expects($this->once())->method('checkRunStatus')->willReturn($checkRunStatusResult);
        $command->expects($this->exactly($completeRunExpected))->method('completeRun');

        $input = new ArrayInput([
            '--id' => '123',
        ]);
        $output = new BufferedOutput();
        $command->run($input, $output);
    }

    /**
     * @return array<int, array<int, bool|int>>
     */
    public static function completeRunMethodProvider(): array
    {
        return [
            [true, 1],  // `completeRun` should be called once
            [false, 0], // `completeRun` should never be called
        ];
    }
}
