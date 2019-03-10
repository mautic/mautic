<?php

namespace MauticPlugin\MauticCrmBundle\Command;

use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export\ActivitiesPipedriveExport;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export\LeadExport;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PushActivitiesToPipedriveCommand extends ContainerAwareCommand
{
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
        $em                = $this->getContainer()->get('doctrine')->getManager();

        if (!$integrationObject->getIntegrationSettings()->getIsPublished()) {
            $output->writeln('Pipedrive integration is disabled.');

            return;
        }

        $output->writeln('<comment>Push activities</comment>');

        /** @var ActivitiesPipedriveExport $activitiesExport */
        $activitiesExport = $this->getContainer()->get('mautic_integration.pipedrive.export.activities');
        $activitiesExport->setIntegration($integrationObject);

        $pushed = 0;
        $start  = 0;
        $limit  = $input->getOption('batch-limit');

        // now() - time-interval
        $timeInterval     = str_replace('--', '-', '-'.$input->getOption('time-interval'));
        $lastPossibleSync = (new \DateTime())->setTimestamp(strtotime($timeInterval));

        $progress = ProgressBarHelper::init($output, $limit);

        while (true) {
            $integrationEntities = $this->getIntegrationEntities($integrationObject, $start, $limit);

            if (!$integrationEntities) {
                break;
            }

            /** @var IntegrationEntity $integrationEntity */
            foreach ($integrationEntities as $integrationEntity) {
                $internal         = $integrationEntity->getInternal();
                $lastActivitySync = null;
                if (isset($internal['last_activity_sync'])) {
                    $lastActivitySync = new \DateTimeImmutable($internal['last_activity_sync']);
                    // stop If last activity sync is over timeout
                    if ($lastActivitySync > $lastPossibleSync) {
                        continue;
                    }
                }
                if ($activitiesExport->createActivities($integrationEntity, $lastActivitySync) !== false) {
                    ++$pushed;
                    if ($pushed % $limit == 0) {
                        $progress->setProgress($pushed);
                    }
                }
            }
            $start = $start + $limit;
            $em->clear();
        }

        $progress->finish();
        $output->writeln('');
        $output->writeln('Pushed activities to '.$pushed.' contacts');
        $output->writeln('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));
    }

    /**
     * @param PipedriveIntegration $integrationObject
     * @param int                  $start
     * @param int                  $limit
     *
     * @return array|null
     */
    private function getIntegrationEntities(PipedriveIntegration $integrationObject, $start, $limit)
    {
        return $integrationObject->getIntegrationEntityRepository()->getEntities(
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
                            'expr'   => 'eq',
                            'value'  => LeadExport::PERSON_ENTITY_TYPE,
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
    }
}
