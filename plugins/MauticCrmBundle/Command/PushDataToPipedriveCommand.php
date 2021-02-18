<?php

namespace MauticPlugin\MauticCrmBundle\Command;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
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
            )
            ->addOption(
                '--batch-limit',
                '-b',
                InputOption::VALUE_OPTIONAL,
                'Set batch size of contacts to process per round. Defaults to 50.',
                50
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
            $this->io->note('Pipedrive integration is disabled.');

            return;
        }

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

        $pushed   = 0;
        $start    = 0;
        $limit    = $input->getOption('batch-limit');

        $totalContactToProcess = $this->getContactWithRequiredDataCount($em);

        $progress = ProgressBarHelper::init($output, $totalContactToProcess);
        while (true) {
            $leads = $this->getLeads($em, $start, $limit);

            if (!$leads) {
                break;
            }

            foreach ($leads as $lead) {
                if ($leadExport->create($lead)) {
                    ++$pushed;
                }
            }
            $start = $start + $limit;
            $progress->setProgress($start);

            $em->clear();
        }

        $progress->finish();

        $output->writeln('');
        $this->io->text('Pushed total '.$pushed);
        $this->io->success('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));
    }

    /**
     * @param EntityManager $em
     * @param int           $start
     * @param int           $limit
     *
     * @return array
     */
    private function getLeads(EntityManager $em, $start, $limit)
    {
        return $em->getRepository(Lead::class)->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'l.email',
                            'expr'   => 'isNotNull',
                        ],
                        [
                            'column' => 'l.firstname',
                            'expr'   => 'isNotNull',
                        ],
                        [
                            'column' => 'l.lastname',
                            'expr'   => 'isNotNull',
                        ],
                    ],
                ],
                'start'            => $start,
                'limit'            => $limit,
                'ignore_paginator' => true,
            ]
        );
    }

    /**
     * Get the count of identified contacts with email, firstname and lastname.
     *
     * @param EntityManager $em
     *
     * @return int
     */
    public function getContactWithRequiredDataCount(EntityManager $em)
    {
        $qb = $em->getConnection()->createQueryBuilder()
            ->select('count(l.id)')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $qb->andWhere(
            $qb->expr()->isNotNull('l.email'),
            $qb->expr()->isNotNull('l.firstname'),
            $qb->expr()->isNotNull('l.lastname')
        );

        return (int) $qb->execute()->fetchColumn();
    }
}
