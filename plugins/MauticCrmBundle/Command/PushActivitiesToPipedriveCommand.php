<?php

namespace MauticPlugin\MauticCrmBundle\Command;

use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export\ActivitiesExport;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export\LeadExport;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PushActivitiesToPipedriveCommand extends ContainerAwareCommand
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
        $this->setName('mautic:integration:pipedrive:pushactivities')
            ->setDescription('Pushes the data from Mautic to Pipedrive')
            ->addOption(
                'time-interval',
                't',
                InputOption::VALUE_OPTIONAL,
                'Send time interval to check updates, it should be a correct php formatted time interval in the past eg:(15 minutes)',
                '15 minutes'
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
            $output->writeln('Pipedrive integration id disabled.');

            return;
        }

        $output->writeln('<comment>Push activities</comment>');

        /** @var ActivitiesExport $activitiesExport */
        $activitiesExport = $this->getContainer()->get('mautic_integration.pipedrive.export.activities');
        $activitiesExport->setIntegration($integrationObject);

        $pushed = 1;
        $start  = 0;
        $limit  = 5;
        // everytime should be negative
        $timeInterval     = str_replace('--', '-', '-'.$input->getOption('time-interval'));
        $lastPossibleSync = (new \DateTime())->setTimestamp(strtotime($timeInterval));
        while (true) {
            $integrationEntities = $integrationObject->getIntegrationEntityRepository()->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'e.integration',
                                'expr'   => 'eq',
                                'value'  => $integrationObject->getName(),
                            ],
                            [
                                'column' => 'e.integrationEntity',
                                'expr'   => 'neq',
                                'value'  => LeadExport::ORGANIZATION_ENTITY_TYPE,
                            ],
                            [
                                'column' => 'e.internalEntity',
                                'expr'   => 'eq',
                                'value'  => LeadExport::LEAD_ENTITY_TYPE,
                            ],
                        ],
                    ],
                    'start'            => $start,
                    'limit'            => $limit,
                    'ignore_paginator' => true,
                ]
            );

            if (!$integrationEntities) {
                break;
            }

            /** @var IntegrationEntity $integrationEntity */
            foreach ($integrationEntities as $integrationEntity) {
                $internal         = $integrationEntity->getInternal();
                $lastActivitySync = null;
                if (isset($internal['last_activity_sync'])) {
                    $lastActivitySync = (new \DateTime())->setTimestamp($internal['last_activity_sync']);
                    // stop If last activity sync is over timeout
                    if ($lastActivitySync > $lastPossibleSync) {
                        continue;
                    }
                }
                $activitiesExport->createActivities($integrationEntity, $lastActivitySync);
            }
            $start = $start + $limit;
            $em->clear();
            if ($start > 20) {
                break;
            }
        }

        $this->io->text('Pushed '.$pushed);
        $this->io->success('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));
    }
}
