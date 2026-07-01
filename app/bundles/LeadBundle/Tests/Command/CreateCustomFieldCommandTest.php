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

final class CreateCustomFieldCommandTest extends TestCase
{
    protected function setUp(): void
    {
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('completeRunMethodProvider')]
    public function testCompleteRunMethodIsCalled(bool $checkRunStatusResult, int $completeRunExpected): void
    {
        $command = $this->getMockBuilder(CreateCustomFieldCommand::class)
            ->setConstructorArgs([
                $this->createStub(BackgroundService::class),
                $this->createStub(TranslatorInterface::class),
                $this->createStub(LeadFieldRepository::class),
                $this->createStub(PathsHelper::class),
                $this->createStub(CoreParametersHelper::class),
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
