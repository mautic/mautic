<?php

namespace Mautic\CampaignBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportCampaignCommand extends ModeratedCommand
{
    use WriteCountTrait;

    public function __construct(
        private CampaignRepository $campaignRepository,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
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
                InputOption::VALUE_REQUIRED,
                'The ID of the campaign to export.'
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
        $campaignId = $input->getOption('id');
        $jsonOnly   = $input->getOption('json-only');
        $jsonFile   = $input->getOption('json-file');
        $zipFile    = $input->getOption('zip-file');

        if (!$campaignId || !is_numeric($campaignId)) {
            $output->writeln('<error>You must specify a valid campaign ID using --id.</error>');

            return self::FAILURE;
        }

        $campaignData = $this->callAnotherCommand('mautic:campaign:fetch', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        if (null === $campaignData) {
            $output->writeln('<error>Failed to fetch campaign data.</error>');

            return self::FAILURE;
        }

        $campaignData = json_decode($campaignData, true);

        $dependenciesData = $this->callAnotherCommand('mautic:campaign:fetch-dependencies', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        if (null === $dependenciesData) {
            $output->writeln('<error>Failed to fetch campaign data.</error>');

            return self::FAILURE;
        }

        $dependenciesData = json_decode($dependenciesData, true);

        $eventData = $this->callAnotherCommand('mautic:campaign:fetch-events', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        if (null === $eventData) {
            $output->writeln('<error>Failed to fetch event data.</error>');

            return self::FAILURE;
        }
        $eventData = json_decode($eventData, true);

        $emailData = $this->callAnotherCommand('mautic:campaign:fetch-emails', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        if (null === $emailData) {
            $output->writeln('<error>Failed to fetch email data.</error>');

            return self::FAILURE;
        }

        $emailData = json_decode($emailData, true);

        $segmentData = $this->callAnotherCommand('mautic:campaign:fetch-segments', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        if (null === $segmentData) {
            $output->writeln('<error>Failed to fetch segment data.</error>');

            return self::FAILURE;
        }

        $segmentData = json_decode($segmentData, true);

        $formData = $this->callAnotherCommand('mautic:campaign:fetch-forms', [
            '--id'        => $campaignId,
            '--json-only' => true,
        ]);

        if (null === $formData) {
            $output->writeln('<error>Failed to fetch segment data.</error>');

            return self::FAILURE;
        }

        $formData = json_decode($formData, true);

        $combinedData = [
            'campaign'     => $campaignData,
            'dependencies' => $dependenciesData,
            'events'       => $eventData,
            'emails'       => $emailData,
            'segments'     => $segmentData,
            'forms'        => $formData,
            'exportedAt'   => (new \DateTime())->format(DATE_ATOM),
        ];

        // Output or save the combined data
        return $this->outputData($combinedData, $input, $output);
    }

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
            $output->writeln('<info>ZIP file created at:</info> '.$zipPath);
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

    private function callAnotherCommand(string $commandName, array $arguments): ?string
    {
        // Initialize the application
        $application = $this->getApplication();
        $command     = $application->find($commandName);

        // Prepare input arguments
        $input = new \Symfony\Component\Console\Input\ArrayInput($arguments);

        // Capture the output
        $output = new \Symfony\Component\Console\Output\BufferedOutput();

        // Execute the command
        $returnCode = $command->run($input, $output);

        // if ($returnCode !== Command::SUCCESS) {
        //     return null;
        // }

        // Return the output as a string
        return $output->fetch();
    }
}
