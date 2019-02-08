<?php

namespace MauticPlugin\MauticCrmBundle\Command;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->setName('mautic:integration:pipedrive:push')
            ->setDescription('Pushes the data from Mautic to Pipedrive')
            ->addOption(
                '--restart',
                null,
                InputOption::VALUE_NONE,
                'Restart intgeration'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $integrationHelper = $this->getContainer()->get('mautic.helper.integration');
        /** @var PipedriveIntegration $integrationObject */
        $integrationObject = $integrationHelper->getIntegrationObject(PipedriveIntegration::INTEGRATION_NAME);
        $this->io          = new SymfonyStyle($input, $output);
        $em                = $this->getContainer()->get('doctrine')->getManager();

        $pushed = 0;

        if (!$integrationObject->getIntegrationSettings()->getIsPublished()) {
            $this->io->note('Pipedrive integration id disabled.');

            return;
        }
        /*   $leadModel = $this->getContainer()->get('mautic.lead.model.lead');
           /** @var LeadExport $leadExport */
        /*    $leadExport= $this->getContainer()->get('mautic_integration.pipedrive.export.lead');
            $leadExport->setIntegration($integrationObject);
            $leadExport->createActivities($leadModel->getEntity(22821),$integrationObject->getIntegrationEntityRepository()->getEntity(1));
die('a');*/
        if ($input->getOption('restart')) {
            $this->io->note(
                $this->getContainer()->get('templating.helper.translator')->trans(
                    'mautic.plugin.config.integration.restarted',
                    ['%integration%' => $integrationObject->getName()]
                )
            );
            $integrationObject->removeIntegrationEntities();
        }

        if ($integrationObject->isCompanySupportEnabled()) {
            $this->io->title('Pushing Companies');
            $companyExport = $this->getContainer()->get('mautic_integration.pipedrive.export.company');
            $companyExport->setIntegration($integrationObject);

            $companies = $em->getRepository(Company::class)->findAll();
            foreach ($companies as $company) {
                if ($companyExport->pushCompany($company)) {
                    ++$pushed;
                }
            }
            $this->io->text('Pushed '.$pushed);
        }

        $this->io->title('Pushing Leads');

        $leadExport = $this->getContainer()->get('mautic_integration.pipedrive.export.lead');
        $leadExport->setIntegration($integrationObject);

        $pushed = 1;
        $start  = 0;
        $limit  = 5;
        while (true) {
            $leads = $em->getRepository(Lead::class)->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'l.email',
                                'expr'   => 'neq',
                                'value'  => '',
                            ],
                            [
                                'column' => 'l.firstname',
                                'expr'   => 'neq',
                                'value'  => '',
                            ],
                            [
                                'column' => 'l.lastname',
                                'expr'   => 'neq',
                                'value'  => '',
                            ],
                        ],
                    ],
                    'start'            => $start,
                    'limit'            => $limit,
                    'ignore_paginator' => true,
                ]
            );

            if (!$leads) {
                break;
            }
            foreach ($leads as $lead) {
                if ($leadExport->create($lead)) {
                    ++$pushed;
                }
            }
            $start = $start + $limit;
            $em->clear();
            break;
        }

        $this->io->text('Pushed '.$pushed);
        $this->io->success('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));
    }
}
