<?php

namespace Mautic\CampaignBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchEmailCommand extends ModeratedCommand
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->setName('mautic:campaign:fetch-emails')
            ->setDescription('Fetch campaign emails based on events.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Campaign ID to fetch emails for.')
            ->addOption('json-only', null, InputOption::VALUE_NONE, 'Output only JSON data.')
            ->addOption('json-file', null, InputOption::VALUE_NONE, 'Save JSON data to a file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $campaignId = $input->getOption('id');

        if (!$campaignId || !is_numeric($campaignId)) {
            $output->writeln('<error>You must specify a valid campaign ID using --id.</error>');

            return self::FAILURE;
        }

        // Fetch relevant events from the campaign_events table
        $connection   = $this->entityManager->getConnection();
        $queryBuilder = $connection->createQueryBuilder();

        $eventResults = $queryBuilder
            ->select('channel_id')
            ->from('campaign_events')
            ->where('campaign_id = :campaignId')
            ->andWhere('channel = :channel')
            ->andWhere('channel_id IS NOT NULL')
            ->andWhere('channel_id != 0')
            ->setParameter('campaignId', $campaignId)
            ->setParameter('channel', 'email')
            ->executeQuery()
            ->fetchAllAssociative();

        if (empty($eventResults)) {
            $output->writeln('<info>No email events found for campaign ID '.$campaignId.'.</info>');

            return self::SUCCESS;
        }

        // Extract channel_ids
        $channelIds = array_column($eventResults, 'channel_id');

        // Fetch emails based on channel_ids
        $emails = $this->entityManager->getRepository(Email::class)->findBy(['id' => $channelIds]);

        $emailData = [];
        foreach ($emails as $email) {
            $emailData[] = [
                'id'               => $email->getId(),
                'subject'          => $email->getSubject(),
                'is_published'     => $email->getIsPublished(),
                'name'             => $email->getName(),
                'description'      => $email->getDescription(),
                'from_address'     => $email->getFromAddress(),
                'from_name'        => $email->getFromName(),
                'reply_to_address' => $email->getReplyToAddress(),
                'preheader_text'   => $email->getPreheaderText(),
                'bcc_address'      => $email->getBccAddress(),
                'template'         => $email->getTemplate(),
                'content'          => $email->getContent(),
                'utm_tags'         => $email->getUtmTags(),
                'plain_text'       => $email->getPlainText(),
                'custom_html'      => $email->getCustomHtml(),
                'email_type'       => $email->getEmailType(),
                'lang'             => $email->getLanguage(),
                'variant_settings' => $email->getVariantSettings(),
                'dynamic_content'  => $email->getDynamicContent(),
                'headers'          => $email->getHeaders(),
            ];
        }

        if (empty($emailData)) {
            $output->writeln('<info>No emails found for the given events.</info>');

            return self::SUCCESS;
        }

        return $this->outputData($emailData, $input, $output);
    }

    private function outputData(array $data, InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = json_encode($data, JSON_PRETTY_PRINT);

        if ($input->getOption('json-only')) {
            $output->writeln($jsonOutput);
        } elseif ($input->getOption('json-file')) {
            $filePath = sprintf('%s/emails_%s.json', sys_get_temp_dir(), $data[0]['id'] ?? 'unknown');
            file_put_contents($filePath, $jsonOutput);
            $output->writeln('<info>JSON file created at:</info> '.$filePath);
        } else {
            $output->writeln('<error>You must specify either --json-only or --json-file.</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
