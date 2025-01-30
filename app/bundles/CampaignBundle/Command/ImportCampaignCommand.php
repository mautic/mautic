<?php

namespace Mautic\CampaignBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCampaignCommand extends ModeratedCommand
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
            ->setName('mautic:campaign:import')
            ->setDescription('Import campaign data from JSON or ZIP file.')
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'The file path of the JSON or ZIP file to import.'
            )
            ->addOption(
                'user',
                null,
                InputOption::VALUE_OPTIONAL,
                'The user ID of the person importing the campaign.'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getOption('file');
        $userId   = $input->getOption('user');

        if (!$filePath || !file_exists($filePath)) {
            $output->writeln('<error>You must specify a valid file path using --file.</error>');

            return self::FAILURE;
        }

        $fileData = $this->readFile($filePath);

        if (null === $fileData) {
            $output->writeln('<error>Failed to read or decode the file data.</error>');

            return self::FAILURE;
        }

        $validationResult = $this->validateCampaignData($fileData);
        if (!$validationResult['isValid']) {
            $output->writeln('<error>Invalid campaign data: '.$validationResult['message'].'</error>');

            return self::FAILURE;
        }

        $this->importCampaign($fileData, $output, is_numeric($userId) ? (int) $userId : null);

        $output->writeln('<info>Campaign data imported successfully.</info>');

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readFile(string $filePath): ?array
    {
        if ('zip' === pathinfo($filePath, PATHINFO_EXTENSION)) {
            $tempDir = sys_get_temp_dir();
            $zip     = new \ZipArchive();

            if (true === $zip->open($filePath)) {
                $jsonFilePath = $zip->getNameIndex(0);
                $zip->extractTo($tempDir);
                $zip->close();
                $filePath = $tempDir.'/'.$jsonFilePath;
            } else {
                return null;
            }
        }

        $fileContents = file_get_contents($filePath);

        return json_decode($fileContents, true);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validateCampaignData(array $data): array
    {
        if (!isset($data['campaigns']) || !isset($data['dependencies'])) {
            return ['isValid' => false, 'message' => 'Missing required keys.'];
        }

        return ['isValid' => true, 'message' => ''];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function importCampaign(array $data, OutputInterface $output, ?int $userId): void
    {
        $campaignIdMap = [];

        $userRepository = $this->entityManager->getRepository(\Mautic\UserBundle\Entity\User::class);

        // Fetch the user if a userId is provided
        $user = null;
        if ($userId) {
            $user = $userRepository->find($userId);
            if (!$user) {
                $output->writeln('<error>User ID '.$userId.' not found. Campaigns will not have a created_by_user field set.</error>');
            }
        }

        // Step 1: Import Campaigns and map original to new IDs
        foreach ($data['campaigns'] as $campaignData) {
            $campaign = new Campaign();
            $campaign->setName($campaignData['name']);
            $campaign->setDescription($campaignData['description'] ?? '');
            $campaign->setIsPublished($campaignData['is_published'] ?? false);
            $campaign->setCanvasSettings($campaignData['canvas_settings'] ?? '');
            $campaign->setDateAdded(new \DateTime());
            $campaign->setDateModified(new \DateTime());

            if ($user) {
                $campaign->setCreatedByUser($user->getFirstName().' '.$user->getLastName());
            }

            $this->entityManager->persist($campaign);
            $this->entityManager->flush();

            $campaignIdMap[$campaignData['id']] = $campaign->getId();

            $output->writeln('<info>Imported campaign: '.$campaign->getName().' with ID: '.$campaign->getId().'</info>');
        }

        // Step 2: Update Dependencies with New Campaign IDs
        $this->updateDependencies($data['dependencies'], $campaignIdMap, 'campaignId', $output);

        // Step 3: Import Other Entities (Segments, Emails, Forms)
        $formIdMap    = $this->importForms($data['forms'] ?? [], $output);
        $segmentIdMap = $this->importSegments($data['segments'] ?? [], $output);
        $emailIdMap   = $this->importEmails($data['emails'] ?? [], $output);

        // Step 4: Update Dependencies for Segments, Forms and Emails
        $this->updateDependencies($data['dependencies'], $formIdMap, 'formId', $output);
        $this->updateDependencies($data['dependencies'], $segmentIdMap, 'segmentId', $output);
        $this->updateDependencies($data['dependencies'], $emailIdMap, 'emailId', $output);

        $this->processDependencies($data['dependencies'], $output);

        // Step 5: Update Events in JSON
        $this->updateEvents($data, $data['dependencies'], $output);

        $eventIdMap = $this->importCampaignEvents($data['events'], $output);

        $this->updateDependencies($data['dependencies'], $eventIdMap, 'eventId', $output);

        // Step 6: Update Event ParentId
        foreach ($data['events'] as $eventData) {
            if (isset($eventData['id'], $eventData['parent_id'], $eventIdMap[$eventData['id']], $eventIdMap[$eventData['parent_id']])) {
                $event = $this->entityManager->getRepository(Event::class)->find($eventIdMap[$eventData['id']]);
                if ($event) {
                    $parentEvent = $this->entityManager->getRepository(Event::class)->find($eventIdMap[$eventData['parent_id']]);
                    if ($parentEvent) {
                        $event->setParent($parentEvent);
                        $this->entityManager->persist($event);
                        $this->entityManager->flush();
                        $output->writeln('<info>before updated: '.print_r($eventIdMap[$eventData['id']], true).'</info>');

                        $output->writeln('<info>Updated parent ID for event: '.$event->getName().'</info>');
                    }
                }
            }
        }

        // Step 7: Update Campaign Canvas Settings
        $this->updateCampaignCanvasSettings($data, $eventIdMap, $campaignIdMap, $output);

        $output->writeln('<info>Campaign canvas settings updated successfully.</info>');
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, int> $eventIdMap
     * @param array<int, int> $campaignIdMap
     */
    private function updateCampaignCanvasSettings(array &$data, array $eventIdMap, array $campaignIdMap, OutputInterface $output): void
    {
        foreach ($data['campaigns'] as &$campaignData) {
            if (!empty($campaignData['canvas_settings'])) {
                $canvasSettings = &$campaignData['canvas_settings'];

                $this->updateCanvasNodes($canvasSettings, $eventIdMap, $output);
                $this->updateCanvasConnections($canvasSettings, $eventIdMap, $output);

                $output->writeln('<info>Updated canvas settings for campaign: '.$campaignData['name'].'</info>');
            }
        }

        $this->persistUpdatedCanvasSettings($data, $campaignIdMap, $output);
    }

    /**
     * @param array<string, mixed> $canvasSettings
     * @param array<int, int> $eventIdMap
     */
    private function updateCanvasNodes(array &$canvasSettings, array $eventIdMap, OutputInterface $output): void
    {
        if (isset($canvasSettings['nodes'])) {
            foreach ($canvasSettings['nodes'] as &$node) {
                if (isset($node['id']) && isset($eventIdMap[$node['id']])) {
                    $node['id'] = $eventIdMap[$node['id']];
                }
            }
            $output->writeln('<info>Canvas nodes updated.</info>');
        }
    }

    /**
     * @param array<string, mixed> $canvasSettings
     * @param array<int, int> $eventIdMap
     */
    private function updateCanvasConnections(array &$canvasSettings, array $eventIdMap, OutputInterface $output): void
    {
        if (isset($canvasSettings['connections'])) {
            foreach ($canvasSettings['connections'] as &$connection) {
                if (isset($connection['sourceId']) && isset($eventIdMap[$connection['sourceId']])) {
                    $connection['sourceId'] = $eventIdMap[$connection['sourceId']];
                }
                if (isset($connection['targetId']) && isset($eventIdMap[$connection['targetId']])) {
                    $connection['targetId'] = $eventIdMap[$connection['targetId']];
                }
            }
            $output->writeln('<info>Canvas connections updated.</info>');
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, int> $campaignIdMap
     */
    private function persistUpdatedCanvasSettings(array &$data, array $campaignIdMap, OutputInterface $output): void
    {
        foreach ($data['campaigns'] as $campaignData) {
            $campaign = $this->entityManager->getRepository(Campaign::class)->find($campaignIdMap[$campaignData['id']] ?? null);

            if ($campaign) {
                $campaign->setCanvasSettings($campaignData['canvas_settings'] ?? '');
                $this->entityManager->persist($campaign);
                $this->entityManager->flush();

                $output->writeln('<info>Persisted updated canvas settings for campaign: '.$campaign->getName().'</info>');
            }
        }
        $output->writeln('<info>Campaign canvas settings updated successfully.</info>');
    }

    /**
     * @param array<int, array<string, mixed>> $dependencies
     * @param array<int, array<string, mixed>|int> $idMap
     */
    private function updateDependencies(array &$dependencies, array $idMap, string $key, OutputInterface $output): void
    {
        foreach ($dependencies as &$dependencyGroup) {
            foreach ($dependencyGroup as &$items) {
                foreach ($items as &$dependency) {
                    if (isset($dependency[$key]) && isset($idMap[$dependency[$key]])) {
                        $originalId       = $dependency[$key];
                        $dependency[$key] = $idMap[$originalId];
                    }
                }
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $forms
     * @return array<int, int>
     */
    private function importForms(array $forms, OutputInterface $output): array
    {
        $formIdMap = [];

        foreach ($forms as $formData) {
            $form = new \Mautic\FormBundle\Entity\Form();
            $form->setName($formData['name']);
            $form->setIsPublished((bool) $formData['is_published']);
            $form->setDescription($formData['description'] ?? '');
            $form->setAlias($formData['alias'] ?? '');
            $form->setLanguage($formData['lang'] ?? null);
            $form->setCachedHtml($formData['cached_html'] ?? '');
            $form->setPostAction($formData['post_action'] ?? '');
            $form->setPostActionProperty($formData['post_action_property'] ?? '');
            $form->setTemplate($formData['template'] ?? '');
            $form->setFormType($formData['form_type'] ?? '');
            $form->setRenderStyle($formData['render_style'] ?? '');
            $form->setFormAttributes($formData['form_attr'] ?? '');
            $form->setDateAdded(new \DateTime());
            $form->setDateModified(new \DateTime());

            $this->entityManager->persist($form);
            $this->entityManager->flush();

            $formIdMap[$formData['id']] = $form->getId();

            $output->writeln('<info>Imported form: '.$form->getName().' with ID: '.$form->getId().'</info>');
        }

        return $formIdMap;
    }

    /**
     * @param array<int, array<string, mixed>> $segments
     * @return array<int, int>
     */
    private function importSegments(array $segments, OutputInterface $output): array
    {
        $segmentIdMap = [];

        foreach ($segments as $segmentData) {
            $segment = new \Mautic\LeadBundle\Entity\LeadList();
            $segment->setName($segmentData['name']);
            $segment->setIsPublished((bool) $segmentData['is_published']);
            $segment->setDescription($segmentData['description'] ?? '');
            $segment->setAlias($segmentData['alias'] ?? '');
            $segment->setPublicName($segmentData['public_name']);
            $segment->setIsGlobal((bool) $segmentData['is_global']);
            $segment->setIsPreferenceCenter((bool) $segmentData['is_preference_center']);
            $segment->setDateAdded(new \DateTime());
            $segment->setDateModified(new \DateTime());

            $filters = $segmentData['filters'] ?? null;
            if ($filters) {
                $filters = is_string($filters) ? unserialize($filters) : $filters; // Convert to array if it's serialized
                if (!is_array($filters)) {
                    $output->writeln('<error>Failed to deserialize filters for segment: '.$segmentData['name'].'</error>');
                    continue;
                }
            }

            $segment->setFilters($filters);

            $this->entityManager->persist($segment);
            $this->entityManager->flush();

            $segmentIdMap[$segmentData['id']] = $segment->getId();

            $output->writeln('<info>Imported segment: '.$segment->getName().' with ID: '.$segment->getId().'</info>');
        }

        return $segmentIdMap;
    }

    /**
     * @param array<int, array<string, mixed>> $emails
     * @return array<int, int>
     */
    private function importEmails(array $emails, OutputInterface $output): array
    {
        $emailIdMap = [];

        foreach ($emails as $emailData) {
            $email = new \Mautic\EmailBundle\Entity\Email();
            $email->setName($emailData['name']);
            $email->setIsPublished((bool) $emailData['is_published']);
            $email->setSubject($emailData['subject'] ?? '');
            $email->setDescription($emailData['description'] ?? '');
            $email->setLanguage($emailData['lang'] ?? null);
            $email->setCustomHtml($emailData['custom_html'] ?? '');
            $email->setTemplate($emailData['template'] ?? '');
            $email->setUtmTags($emailData['utm_tags'] ?? []);
            $email->setDynamicContent($emailData['dynamic_content'] ?? []);
            $email->setHeaders($emailData['headers'] ?? []);

            $email->setDynamicContent($emailData['dynamic_content'] ?? []);
            $email->setVariantSettings($emailData['variant_settings'] ?? []);
            $email->setEmailType($emailData['email_type'] ?? '');
            $email->setPlainText($emailData['plain_text'] ?? '');
            $email->setContent($emailData['content'] ?? []);
            $email->setBccAddress($emailData['bcc_address'] ?? '');
            $email->setPreheaderText($emailData['preheader_text'] ?? '');
            $email->setReplyToAddress($emailData['reply_to_address'] ?? '');
            $email->setFromName($emailData['from_name'] ?? '');
            $email->setFromAddress($emailData['from_address'] ?? '');
            $email->setDateAdded(new \DateTime());
            $email->setDateModified(new \DateTime());

            $this->entityManager->persist($email);
            $this->entityManager->flush();

            $emailIdMap[$emailData['id']] = $email->getId();

            $output->writeln('<info>Imported email: '.$email->getName().' with ID: '.$email->getId().'</info>');
        }

        return $emailIdMap;
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array<int, int>
     */
    private function importCampaignEvents(array $events, OutputInterface $output): array
    {
        $eventIdMap = [];

        foreach ($events as $eventData) {
            try {
                // Fetch the Campaign entity using the campaign_id
                $campaign = $this->entityManager->getRepository(Campaign::class)->find($eventData['campaign_id']);
                if (!$campaign) {
                    $output->writeln('<error>Failed to find campaign with ID: '.$eventData['campaign_id'].' for event: '.$eventData['name'].'</error>');
                    continue;
                }

                // Create the Event entity
                $event = new Event();
                $event->setName($eventData['name']);
                $event->setCampaign($campaign); // Set the Campaign object
                $event->setDescription($eventData['description'] ?? '');
                $event->setType($eventData['type']);
                $event->setEventType($eventData['event_type']);
                $event->setOrder($eventData['event_order']);
                $event->setTriggerInterval($eventData['trigger_interval'] ?? null);
                $event->setTriggerIntervalUnit($eventData['trigger_interval_unit'] ?? null);
                $event->setTriggerMode($eventData['trigger_mode'] ?? null);
                $event->setTriggerDate(isset($eventData['triggerDate']) ? new \DateTime($eventData['triggerDate']) : null);
                $event->setChannel($eventData['channel'] ?? null);
                $event->setChannelId($eventData['channel_id'] ?? null);
                $event->setProperties($eventData['properties'] ?? []);

                $this->entityManager->persist($event);
                $this->entityManager->flush();

                // Map the old event ID to the new event ID
                $eventIdMap[$eventData['id']] = $event->getId();

                $output->writeln('<info>Imported event: '.$event->getName().' with ID: '.$event->getId().'</info>');
            } catch (\Exception $e) {
                $output->writeln('<error>Failed to import event: '.($eventData['name'] ?? '[Unnamed Event]').'. Error: '.$e->getMessage().'</error>');
            }
        }

        return $eventIdMap;
    }

    /**
     * @param array<int, array<string, mixed>> $dependencies
     */
    private function processDependencies(array $dependencies, OutputInterface $output): void
    {
        foreach ($dependencies as &$dependencyGroup) {
            foreach ($dependencyGroup as $key => $items) {
                // Process form dependencies for campaign_form_xref
                if ('form' === $key) {
                    foreach ($items as &$dependency) {
                        $campaignId = $dependency['campaignId'];
                        $formId     = $dependency['formId'];

                        $this->insertCampaignFormXref($campaignId, $formId, $output);
                    }
                }
                // Process segment dependencies for campaign_leadlist_xref
                if ('segment' === $key) {
                    foreach ($items as &$dependency) {
                        $campaignId = $dependency['campaignId'];
                        $segmentId  = $dependency['segmentId'];

                        $this->insertCampaignSegmentXref($campaignId, $segmentId, $output);
                    }
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $dependencies
     */
    private function updateEvents(array &$data, array $dependencies, OutputInterface $output): void
    {
        if (empty($data['events'])) {
            $output->writeln('<info>No events found to update.</info>');

            return;
        }

        $eventDependencies = $this->getEventDependencies($dependencies);

        if (empty($eventDependencies)) {
            $output->writeln('<info>No event dependencies found in the dependencies.</info>');

            return;
        }

        foreach ($data['events'] as &$event) {
            $this->updateEventCampaignId($event, $eventDependencies, $output);
            $this->updateEventChannelId($event, $dependencies, $output);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $dependencies
     * @return array<int, array<string, mixed>>
     */
    private function getEventDependencies(array $dependencies): array
    {
        foreach ($dependencies as $dependencyGroup) {
            if (isset($dependencyGroup['event'])) {
                return $dependencyGroup['event'];
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $event
     * @param array<int, array<string, mixed>> $eventDependencies
     */
    private function updateEventCampaignId(array &$event, array $eventDependencies, OutputInterface $output): void
    {
        foreach ($eventDependencies as $eventDependency) {
            if (isset($event['id']) && $event['id'] === $eventDependency['eventId']) {
                $event['campaign_id'] = $eventDependency['campaignId'];
                $output->writeln("<info>Updated event ID {$event['id']} with campaignId {$event['campaign_id']}.</info>");
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $dependencies
     * @param array<string, mixed> $event
     */
    private function updateEventChannelId(array &$event, array $dependencies, OutputInterface $output): void
    {
        if (!isset($event['channel']) || 'email' !== $event['channel']) {
            return;
        }

        foreach ($dependencies as $dependencyGroup) {
            if (!isset($dependencyGroup['email'])) {
                continue;
            }

            foreach ($dependencyGroup['email'] as $emailDependency) {
                if (isset($event['id']) && $event['id'] === $emailDependency['eventId']) {
                    $event['channel_id'] = $emailDependency['emailId'];
                    $output->writeln("<info>Updated event ID {$event['id']} with channelId {$event['channel_id']}.</info>");
                }
            }
        }
    }

    private function insertCampaignFormXref(int $campaignId, int $formId, OutputInterface $output): void
    {
        try {
            $connection = $this->entityManager->getConnection();
            $connection->insert('campaign_form_xref', [
                'campaign_id' => $campaignId,
                'form_id'     => $formId,
            ]);

            $output->writeln("<info>Inserted campaign_form_xref: campaign_id={$campaignId}, form_id={$formId}</info>");
        } catch (\Exception $e) {
            $output->writeln('<error>Failed to insert into campaign_form_xref: '.$e->getMessage().'</error>');
        }
    }

    private function insertCampaignSegmentXref(int $campaignId, int $segmentId, OutputInterface $output): void
    {
        try {
            $connection = $this->entityManager->getConnection();
            $connection->insert('campaign_leadlist_xref', [
                'campaign_id' => $campaignId,
                'leadlist_id' => $segmentId,
            ]);

            $output->writeln("<info>Inserted campaign_leadlist_xref: campaign_id={$campaignId}, leadlist_id={$segmentId}</info>");
        } catch (\Exception $e) {
            $output->writeln('<error>Failed to insert into campaign_leadlist_xref: '.$e->getMessage().'</error>');
        }
    }
}
