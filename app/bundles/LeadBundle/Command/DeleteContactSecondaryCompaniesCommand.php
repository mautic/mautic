<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Doctrine\ORM\Exception\ORMException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteContactSecondaryCompaniesCommand extends Command
{
    protected static $defaultDescription = 'Deletes all contact\'s secondary companies.';
    public const NAME                    = 'mautic:contact:delete:secondary-companies';

    public function __construct(private LoggerInterface $logger, private TranslatorInterface $translator, private CoreParametersHelper $coreParametersHelper, private CompanyLeadRepository $companyLeadsRepository)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command deletes non-primary companies of every contact.

<info>php %command.full_name%</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $allowMultiple = $this->coreParametersHelper->get('contact_allow_multiple_companies');

        // We process only if the config is set to false
        if ($allowMultiple) {
            $output->writeln($this->translator->trans('mautic.lead.command.delete_contact_secondary_company.allow_multiple_enabled'));

            return Command::SUCCESS;
        }

        try {
            $this->companyLeadsRepository->removeAllSecondaryCompanies();
        } catch (ORMException $e) {
            $errorMessage = $this->translator->trans('mautic.lead.command.error', ['%name%' => self::NAME, '%error%' => $e->getMessage()]);
            $output->writeln($errorMessage);
            $this->logger->error($errorMessage);
        }

        $output->writeln($this->translator->trans('mautic.lead.command.delete_contact_secondary_company.success'));

        return Command::SUCCESS;
    }
}
