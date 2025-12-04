<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Command;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\LeadBundle\Field\BackgroundService;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Field\Exception\LeadFieldWasNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(name: 'mautic:custom-field:update-column', description: 'Create custom field column in the background', help: <<<'TXT'
The <info>%command.name%</info> command will create a column in a lead_fields table if the proces should run in background.

<info>php %command.full_name%</info>
TXT)]
class UpdateCustomFieldCommand
{
    public function __construct(private BackgroundService $backgroundService, private TranslatorInterface $translator)
    {
    }

    public function __invoke(#[\Symfony\Component\Console\Attribute\Option(name: '--id', shortcut: '-i', mode: InputOption::VALUE_REQUIRED, description: 'LeadField ID.')]
        $id, #[\Symfony\Component\Console\Attribute\Option(name: '--user', shortcut: '-u', mode: InputOption::VALUE_OPTIONAL, description: 'User ID - User which receives a notification.')]
        $user, OutputInterface $output): int
    {
        $leadFieldId = (int) $id;
        $userId      = (int) $user;

        try {
            $this->backgroundService->updateColumn($leadFieldId, $userId);
        } catch (LeadFieldWasNotFoundException) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.field.notfound').'</error>');

            return Command::FAILURE;
        } catch (AbortColumnUpdateException) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.field.column_update_aborted').'</error>');

            return Command::SUCCESS;
        } catch (DriverException|SchemaException|DBALException|\Mautic\CoreBundle\Exception\SchemaException $e) {
            $output->writeln('<error>'.$this->translator->trans($e->getMessage()).'</error>');

            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<info>'.$this->translator->trans('mautic.lead.field.column_was_updated').'</info>');

        return Command::SUCCESS;
    }
}
