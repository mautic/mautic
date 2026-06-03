<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Command\UpdateCompanySegmentsCommand;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Mautic\LeadBundle\Services\CompanySegmentRebuildService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpdateCompanySegmentsCommandTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('providePositiveIntegerOrNullOption')]
    public function testIdRequiresNoneOrPositiveInteger(int|string|null $segmentId, bool $valid): void
    {
        $companySegmentModel  = $this->createMock(CompanySegmentModel::class);
        $rebuildService       = $this->createMock(CompanySegmentRebuildService::class);
        $translator           = $this->createMock(TranslatorInterface::class);
        $pathsHelper          = $this->createMock(PathsHelper::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::once())
            ->method('writeln')
            ->with(
                $valid
                    ? '<error>The --batch-limit option must be a positive number.</error>'
                    : '<error>The --segment-id option must be a positive number or none.</error>'
            );

        $arguments = [
            '--bypass-locking' => null,
            '--batch-limit'    => 'aaa',
        ];

        if (null !== $segmentId) {
            $arguments['--segment-id'] = $segmentId;
        }

        $command = new UpdateCompanySegmentsCommand($companySegmentModel, $rebuildService, $translator, $pathsHelper, $coreParametersHelper);
        $command->setApplication(new Application());
        self::assertSame(Command::FAILURE, $command->run(new ArrayInput($arguments), $output));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePositiveIntegerOption')]
    public function testBatchLimitRequiresPositiveInteger(int|string $batchLimit, bool $valid): void
    {
        $companySegmentModel  = $this->createMock(CompanySegmentModel::class);
        $rebuildService       = $this->createMock(CompanySegmentRebuildService::class);
        $translator           = $this->createMock(TranslatorInterface::class);
        $pathsHelper          = $this->createMock(PathsHelper::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::once())
            ->method('writeln')
            ->with(
                $valid
                    ? '<error>The --max-companies option must be a positive number or none.</error>'
                    : '<error>The --batch-limit option must be a positive number.</error>'
            );

        $arguments = [
            '--bypass-locking' => null,
            '--batch-limit'    => $batchLimit,
            '--max-companies'  => 'aaa',
        ];

        $command = new UpdateCompanySegmentsCommand($companySegmentModel, $rebuildService, $translator, $pathsHelper, $coreParametersHelper);
        $command->setApplication(new Application());
        self::assertSame(Command::FAILURE, $command->run(new ArrayInput($arguments), $output));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePositiveIntegerOrNullOption')]
    public function testMaxCompaniesRequiresNoneOrPositiveInteger(int|string|null $maxCompanies, bool $valid): void
    {
        $companySegmentModel  = $this->createMock(CompanySegmentModel::class);
        $rebuildService       = $this->createMock(CompanySegmentRebuildService::class);
        $translator           = $this->createMock(TranslatorInterface::class);
        $pathsHelper          = $this->createMock(PathsHelper::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $companySegmentModel->method('getEntity')
            ->willReturn(new CompanySegment());

        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::once())
            ->method('writeln')
            ->with(
                $valid
                    ? '<error></error>'
                    : '<error>The --max-companies option must be a positive number or none.</error>'
            );

        $arguments = [
            '--bypass-locking' => null,
            '--segment-id'     => 11,
        ];

        if (null !== $maxCompanies) {
            $arguments['--max-companies'] = $maxCompanies;
        }

        $command = new UpdateCompanySegmentsCommand($companySegmentModel, $rebuildService, $translator, $pathsHelper, $coreParametersHelper);
        $command->setApplication(new Application());
        self::assertSame(Command::FAILURE, $command->run(new ArrayInput($arguments), $output));
    }

    public static function providePositiveIntegerOption(): \Generator
    {
        yield 'string' => ['aaa', false];
        yield 'negative' => [-1, false];
        yield 'zero' => [0, false];
        yield 'positive' => [1, true];
    }

    public static function providePositiveIntegerOrNullOption(): \Generator
    {
        yield from self::providePositiveIntegerOption();
        yield 'null' => [null, true];
    }
}
