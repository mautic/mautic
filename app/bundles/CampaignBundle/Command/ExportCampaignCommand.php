<?php

namespace Mautic\CampaignBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCampaignCommand extends ModeratedCommand
{
    use WriteCountTrait;

    public function __construct(
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->setName('mautic:campaign:export')
            ->setDescription('Export campaign data as JSON.')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The ID(s) of the campaign(s) to export. Use comma-separated values for multiple IDs.'
            )
            ->addOption(
                'json-only',
                null,
                InputOption::VALUE_NONE,
                'Output only JSON data.'
            )
            ->addOption(
                'json-file',
                null,
                InputOption::VALUE_NONE,
                'Save JSON data to a file.'
            )
            ->addOption(
                'zip-file',
                null,
                InputOption::VALUE_NONE,
                'Save JSON data to a zip file.'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $campaignIds = $input->getOption('id');
        if (!$campaignIds || !is_array($campaignIds)) {
            $output->writeln('<error>You must specify at least one valid campaign ID using --id.</error>');

            return self::FAILURE;
        }

        $combinedData = [
            'campaigns'    => [],
            'events'       => [],
            'segments'     => [],
            'emails'       => [],
            'forms'        => [],
            'dependencies' => [],
            'exportedAt'   => (new \DateTime())->format(DATE_ATOM),
        ];

        foreach ($campaignIds as $campaignId) {
            $campaignData = $this->fetchCampaignData($campaignId);
            if (!$campaignData) {
                continue;
            }

            $eventData   = $this->fetchEvents($campaignId);
            $segmentData = $this->fetchSegments($campaignId);
            $formData    = $this->fetchForms($campaignId);
            $emailData   = $this->fetchEmails($campaignId);

            $combinedData['campaigns'][] = array_merge($combinedData['campaigns'], $campaignData);
            $this->mergeDependencies(
                $combinedData['dependencies'],
                $campaignId,
                $eventData,
                $segmentData,
                $formData,
                $emailData
            );

            $combinedData['events']   = array_merge($combinedData['events'], $eventData);
            $combinedData['segments'] = array_merge($combinedData['segments'], $segmentData);
            $combinedData['forms']    = array_merge($combinedData['forms'], $formData);
            $combinedData['emails']   = array_merge($combinedData['emails'], $emailData);
        }

        // Output or save the combined data
        return $this->outputData($combinedData, $input, $output);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchCampaignData(string $campaignId): ?array
    {
        $campaignData = $this->callAnotherCommand('mautic:campaign:fetch', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        return $campaignData ? json_decode($campaignData, true) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchEvents(string $campaignId): array
    {
        $eventData = $this->callAnotherCommand('mautic:campaign:fetch-events', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        return $eventData ? json_decode($eventData, true) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchEmails(string $campaignId): array
    {
        $emailData = $this->callAnotherCommand('mautic:campaign:fetch-emails', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        return $emailData ? json_decode($emailData, true) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSegments(string $campaignId): array
    {
        $segmentData = $this->callAnotherCommand('mautic:campaign:fetch-segments', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        return $segmentData ? json_decode($segmentData, true) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchForms(string $campaignId): array
    {
        $formData = $this->callAnotherCommand('mautic:campaign:fetch-forms', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        return $formData ? json_decode($formData, true) : [];
    }

    /**
     * @param array<int, array<string, mixed>> $dependencies
     * @param array<int, array<string, mixed>> $eventData
     * @param array<int, array<string, mixed>> $segmentData
     * @param array<int, array<string, mixed>> $formData
     * @param array<int, array<string, mixed>> $emailData
     */
    private function mergeDependencies(array &$dependencies, string $campaignId, array $eventData, array $segmentData, array $formData, array $emailData): void
    {
        $dependency = [
            'event'   => [],
            'segment' => [],
            'form'    => [],
            'email'   => [],
        ];

        foreach ($eventData as $event) {
            $dependency['event'][] = [
                'campaignId' => (int) $campaignId,
                'eventId'    => (int) $event['id'],
            ];
        }

        foreach ($segmentData as $segment) {
            $dependency['segment'][] = [
                'campaignId' => (int) $campaignId,
                'segmentId'  => (int) $segment['id'],
            ];
        }

        foreach ($formData as $form) {
            $dependency['form'][] = [
                'campaignId' => (int) $campaignId,
                'formId'     => (int) $form['id'],
            ];
        }

        foreach ($emailData as $email) {
            foreach ($eventData as $event) {
                if ('email' === $event['channel']) {
                    $dependency['email'][] = [
                        'eventId' => (int) $event['id'],
                        'emailId' => (int) $email['id'],
                    ];
                }
            }
        }

        $dependencies[] = $dependency;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function outputData(array $data, InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = json_encode($data, JSON_PRETTY_PRINT);

        if ($input->getOption('json-only')) {
            $output->writeln($jsonOutput);
        } elseif ($input->getOption('json-file')) {
            $filePath = $this->writeToFile($jsonOutput);
            $output->writeln('<info>JSON file created at:</info> '.$filePath);
        } elseif ($input->getOption('zip-file')) {
            $zipPath = $this->writeToZipFile($jsonOutput);
            $output->writeln(''.$zipPath);
        } else {
            $output->writeln('<error>You must specify one of --json-only, --json-file, or --zip-file options.</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function writeToFile(string $jsonOutput): string
    {
        $filePath = sprintf('%s/campaign_data.json', sys_get_temp_dir());
        file_put_contents($filePath, $jsonOutput);

        return $filePath;
    }

    private function writeToZipFile(string $jsonOutput): string
    {
        $tempDir      = sys_get_temp_dir();
        $jsonFilePath = sprintf('%s/campaign_data.json', $tempDir);
        $zipFilePath  = sprintf('%s/campaign_data.zip', $tempDir);

        file_put_contents($jsonFilePath, $jsonOutput);

        $zip = new \ZipArchive();
        if (true === $zip->open($zipFilePath, \ZipArchive::CREATE)) {
            $zip->addFile($jsonFilePath, 'campaign_data.json');
            $zip->close();
        }

        return $zipFilePath;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function callAnotherCommand(string $commandName, array $arguments): string
    {
        $application = $this->getApplication();
        $command     = $application->find($commandName);

        $input = new \Symfony\Component\Console\Input\ArrayInput($arguments);

        $output = new \Symfony\Component\Console\Output\BufferedOutput();

        $command->run($input, $output);

        return $output->fetch();
    }
}
