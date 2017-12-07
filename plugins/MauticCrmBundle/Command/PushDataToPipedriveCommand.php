<?php

namespace MauticPlugin\MauticCrmBundle\Command;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PushDataToPipedriveCommand extends ContainerAwareCommand
{
    /**
     * @var SymfonyStyle
     */
    private $io;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:integration:pipedrive:push');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $integrationHelper = $this->getContainer()->get('mautic.helper.integration');
        $integrationObject = $integrationHelper->getIntegrationObject(PipedriveIntegration::INTEGRATION_NAME);
        $this->io          = new SymfonyStyle($input, $output);
        $em                = $this->getContainer()->get('doctrine')->getManager();

        $pushed = 0;

        if (!$integrationObject->getIntegrationSettings()->getIsPublished()) {
            $this->io->note('Pipedrive integration id disabled.');

            return;
        }

        if ($integrationObject->isCompanySupportEnabled()) {
            $this->io->title('Pushing Companies');
            $companyExport = $this->getContainer()->get('mautic_integration.pipedrive.export.company');
            $companyExport->setIntegration($integrationObject);

            $companies = $em->getRepository(Company::class)->findAll();
            foreach ($companies as $company) {
                if ($companyExport->create($company)) {
                    ++$pushed;
                }
            }
        }

        $this->io->text('Pushed '.$pushed);

        $leads = $em->getRepository(Lead::class)->findAll();
        $this->io->title('Pushing Leads');
        $leadExport = $this->getContainer()->get('mautic_integration.pipedrive.export.lead');
        $leadExport->setIntegration($integrationObject);
        $pushed = 0;
        foreach ($leads as $lead) {
            if ($leadExport->create($lead)) {
                ++$pushed;
            }
        }
        $this->io->text('Pushed '.$pushed);

        $this->io->success('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));
    }
}
