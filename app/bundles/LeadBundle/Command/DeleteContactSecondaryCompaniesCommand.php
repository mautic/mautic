<?php

namespace Mautic\LeadBundle\Command;

use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DeleteContactSecondaryCompaniesCommand extends ContainerAwareCommand
{
    const NAME = 'mautic:contact:delete:secondary-companies';

    private LoggerInterface $logger;

    private TranslatorInterface $translator;

    private CoreParametersHelper $coreParametersHelper;

    private CompanyLeadRepository $companyLeadsRepository;

    public function __construct(LoggerInterface $logger, TranslatorInterface $translator, CoreParametersHelper $coreParametersHelper, CompanyLeadRepository $companyLeadsRepository)
    {
        parent::__construct();
        $this->logger                 = $logger;
        $this->translator             = $translator;
        $this->coreParametersHelper   = $coreParametersHelper;
        $this->companyLeadsRepository = $companyLeadsRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Deletes all contact\'s secondary companies.')
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command deletes non primary companies of every contact.

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

            return 0;
        }

        try {
            $this->companyLeadsRepository->removeAllSecondaryCompanies();
        } catch (ORMException $e) {
            $errorMessage = $this->translator->trans('mautic.lead.command.error', ['%name%' => self::NAME, '%error%' => $e->getMessage()]);
            $output->writeln($errorMessage);
            $this->logger->error($errorMessage);
        }

        $output->writeln($this->translator->trans('mautic.lead.command.delete_contact_secondary_company.success'));

        return 0;
    }
}
