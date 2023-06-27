<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Command;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Field\BackgroundService;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\ColumnAlreadyCreatedException;
use Mautic\LeadBundle\Field\Exception\CustomFieldLimitException;
use Mautic\LeadBundle\Field\Exception\LeadFieldWasNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateCustomFieldCommand extends ModeratedCommand
{
    public const COMMAND_NAME = 'mautic:custom-field:create-column';

    protected static $defaultDescription = 'Create custom field column in the background';

    private BackgroundService $backgroundService;
    private TranslatorInterface $translator;
    private LeadFieldRepository $leadFieldRepository;

    public function __construct(
        BackgroundService $backgroundService,
        TranslatorInterface $translator,
        LeadFieldRepository $leadFieldRepository,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
        $this->backgroundService   = $backgroundService;
        $this->translator          = $translator;
        $this->leadFieldRepository = $leadFieldRepository;
    }

    public function configure(): void
    {
        parent::configure();

        $this->setName(self::COMMAND_NAME)
            ->addOption('--id', '-i', InputOption::VALUE_REQUIRED, 'LeadField ID.')
            ->addOption('--user', '-u', InputOption::VALUE_OPTIONAL, 'User ID - User which receives a notification.')
            ->addOption('--all', '-a', InputOption::VALUE_NONE, 'Create all columns which have not been created yet. This option does not work with --id option.')
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command will create a column in a lead_fields table if the process should run in background.

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $leadFieldId = (int) $input->getOption('id');
        $userId      = (int) $input->getOption('user');
        $all         = (bool) $input->getOption('all');

        if ($all && $leadFieldId) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.field.all_option_conflict').'</error>');

            return Command::FAILURE;
        }

        if ($all) {
            return $this->addAllMissingColumns($input, $output) ? Command::SUCCESS : Command::FAILURE;
        }

        if (!$leadFieldId) {
            @trigger_error('Must pass an id with the --id flag or use the --all flag. Future versions will use --all as the default behaviour', E_USER_DEPRECATED);

            return $this->findAndAddColumn($input, $output) ? Command::SUCCESS : Command::FAILURE;
        }

        return $this->addColumn($leadFieldId, $userId, $input, $output) ? Command::SUCCESS : Command::FAILURE;
    }

    private function addAllMissingColumns(InputInterface $input, OutputInterface $output): bool
    {
        $hasNoErrors = true;
        while ($leadField = $this->leadFieldRepository->getFieldThatIsMissingColumn()) {
            if (!$this->addColumn($leadField->getId(), $leadField->getCreatedBy(), $input, $output)) {
                $hasNoErrors = false;
            }
        }

        return $hasNoErrors;
    }

    private function findAndAddColumn(InputInterface $input, OutputInterface $output): bool
    {
        $leadField = $this->leadFieldRepository->getFieldThatIsMissingColumn();

        if (!$leadField) {
            $output->writeln('<info>'.$this->translator->trans('mautic.lead.field.all_fields_have_columns').'</info>');

            return true;
        }

        return $this->addColumn($leadField->getId(), $leadField->getCreatedBy(), $input, $output);
    }

    private function addColumn(int $leadFieldId, ?int $userId, InputInterface $input, OutputInterface $output): bool
    {
        $moderationKey = sprintf('%s-%s-%s', self::COMMAND_NAME, $leadFieldId, $userId);

        if (!$this->checkRunStatus($input, $output, $moderationKey)) {
            return true;
        }

        try {
            $this->backgroundService->addColumn($leadFieldId, $userId);
        } catch (LeadFieldWasNotFoundException $e) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.field.notfound').'</error>');

            return false;
        } catch (ColumnAlreadyCreatedException $e) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.field.column_already_created').'</error>');

            return true;
        } catch (AbortColumnCreateException $e) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.field.column_creation_aborted').'</error>');

            return true;
        } catch (CustomFieldLimitException $e) {
            $output->writeln('<error>'.$this->translator->trans($e->getMessage()).'</error>');

            return false;
        } catch (DriverException $e) {
            $output->writeln('<error>'.$this->translator->trans($e->getMessage()).'</error>');

            return false;
        } catch (SchemaException $e) {
            $output->writeln('<error>'.$this->translator->trans($e->getMessage()).'</error>');

            return false;
        } catch (\Doctrine\DBAL\Exception $e) {
            $output->writeln('<error>'.$this->translator->trans($e->getMessage()).'</error>');

            return false;
        } catch (\Mautic\CoreBundle\Exception\SchemaException $e) {
            $output->writeln('<error>'.$this->translator->trans($e->getMessage()).'</error>');

            return false;
        }

        $output->writeln('');
        $output->writeln('<info>'.$this->translator->trans('mautic.lead.field.column_was_created', ['%id%' => $leadFieldId]).'</info>');
        $this->completeRun();

        return true;
    }
}
