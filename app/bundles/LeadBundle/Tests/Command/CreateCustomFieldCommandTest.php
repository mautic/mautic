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
    private BackgroundService $backgroundServiceMock;

    private TranslatorInterface $translatorMock;

    private LeadFieldRepository $leadFieldRepositoryMock;

    private PathsHelper $pathsHelperMock;

    private CoreParametersHelper $coreParametersHelper;

    protected function setUp(): void
    {
        $this->backgroundServiceMock   = $this->createMock(BackgroundService::class);
        $this->translatorMock          = $this->createMock(TranslatorInterface::class);
        $this->leadFieldRepositoryMock = $this->createMock(LeadFieldRepository::class);
        $this->pathsHelperMock         = $this->createMock(PathsHelper::class);
        $this->coreParametersHelper    = $this->createMock(CoreParametersHelper::class);
    }

    /**
     * @dataProvider completeRunMethodProvider
     */
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
