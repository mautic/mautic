<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Command;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Field\BackgroundService;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Field\Exception\LeadFieldWasNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeleteCustomFieldCommand extends Command
{
    public function __construct(
        private BackgroundService $backgroundService,
        private TranslatorInterface $translator,
        private LeadFieldRepository $leadFieldRepository,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();

        $this->setName('mautic:custom-field:delete-column')
            ->setDescription('Delete custom field column in the background')
            ->addOption('--id', '-i', InputOption::VALUE_REQUIRED, 'LeadField ID.')
            ->addOption('--user', '-u', InputOption::VALUE_OPTIONAL, 'User ID - User which receives a notification.')
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command will delete a column in a lead_fields table if the proces should run in background.

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $leadFieldId = (int) $input->getOption('id');
        $userId      = (int) $input->getOption('user');

        // Field ID wasn't provided. Try to find a field that is marked for deletion.
        if (!$leadFieldId) {
            /** @var ?LeadField $field */
            $field = $this->leadFieldRepository->findOneBy(['columnIsNotRemoved' => true]);

            if ($field) {
                $output->writeln('<info>'.$this->translator->trans(
                    'mautic.lead.field.column_was_found_for_deletion',
                    ['%fieldName%' => $field->getName(), '%fieldId%' => $field->getId()]
                ).'</info>');

                $leadFieldId = $field->getId();

                if (!$userId && $field->getModifiedBy()) {
                    $userId = $field->getModifiedBy();
                }
            }
        }

        try {
            $this->backgroundService->deleteColumn($leadFieldId, $userId);
        } catch (LeadFieldWasNotFoundException) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.field.notfound').'</error>');

            return Command::FAILURE;
        } catch (AbortColumnUpdateException) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.field.column_delete_aborted').'</error>');

            return Command::SUCCESS;
        } catch (DriverException|SchemaException|\Mautic\CoreBundle\Exception\SchemaException $e) {
            $output->writeln('<error>'.$this->translator->trans($e->getMessage()).'</error>');

            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<info>'.$this->translator->trans('mautic.lead.field.column_was_deleted').'</info>');

        return Command::SUCCESS;
    }
}
