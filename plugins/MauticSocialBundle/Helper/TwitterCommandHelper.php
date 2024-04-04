<?php

namespace MauticPlugin\MauticSocialBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;
use MauticPlugin\MauticSocialBundle\Exception\ExitMonitorException;
use MauticPlugin\MauticSocialBundle\Model\MonitoringModel;
use MauticPlugin\MauticSocialBundle\Model\PostCountModel;
use Symfony\Component\Console\Output\OutputInterface;

class TwitterCommandHelper
{
    private ?\Symfony\Component\Console\Output\OutputInterface $output = null;

    private int $updatedLeads = 0;

    private int $newLeads = 0;

    private array $manipulatedLeads = [];

    /**
     * @var string
     */
    private $twitterHandleField;

    public function __construct(
        private LeadModel $leadModel,
        private FieldModel $fieldModel,
        private MonitoringModel $monitoringModel,
        private PostCountModel $postCountModel,
        private Translator $translator,
        private EntityManagerInterface $em,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->translator->setLocale($coreParametersHelper->get('locale', 'en_US'));
        $this->twitterHandleField = $coreParametersHelper->get('twitter_handle_field', 'twitter');
    }

    public function getNewLeadsCount(): int
    {
        return $this->newLeads;
    }

    public function getUpdatedLeadsCount(): int
    {
        return $this->updatedLeads;
    }

    /**
     * @return array
     */
    public function getManipulatedLeads()
    {
        return $this->manipulatedLeads;
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @param string $message
     * @param bool   $newLine
     */
    private function output($message, $newLine = true): void
    {
        if ($newLine) {
            $this->output->writeln($message);
        } else {
            $this->output->write($message);
        }
    }

    /**
     * Processes a list of tweets and creates / updates leads in Mautic.
     *
     * @param array      $statusList
     * @param Monitoring $monitor
     */
    public function createLeadsFromStatuses($statusList, $monitor): int
    {
        $leadField = $this->fieldModel->getRepository()->findOneBy(['alias' => $this->twitterHandleField]);

        if (!$leadField) {
            // Field has been deleted or something
            $this->output($this->translator->trans('mautic.social.monitoring.twitter.field.not.found'));

            return 0;
        }

        $handleFieldGroup = $leadField->getGroup();

        // Just a means to let any LeadEvents listeners know that many leads are likely coming in case that matters to their logic
        defined('MASS_LEADS_MANIPULATION') or define('MASS_LEADS_MANIPULATION', 1);
        defined('SOCIAL_MONITOR_IMPORT') or define('SOCIAL_MONITOR_IMPORT', 1);

        // Get a list of existing leads to tone down on queries
        $usersByHandles    = [];
        $usersByName       = ['firstnames' => [], 'lastnames' => []];
        $expr              = $this->leadModel->getRepository()->createQueryBuilder('f')->expr();
        $monitorProperties = $monitor->getProperties();

        if (!array_key_exists('checknames', $monitorProperties)) {
            $monitorProperties['checknames'] = 0;
        }

        foreach ($statusList as $i => $status) {
            // If we don't have a screen_name, the rest is irrelevant. Remove from further processing
            if (empty($status['user']['screen_name'])) {
                unset($statusList[$i]);
                continue;
            }

            $usersByHandles[] = $expr->literal($status['user']['screen_name']);

            // Split the twitter user's name into its parts if we're matching to contacts by name
            if ($monitorProperties['checknames'] && $status['user']['name'] && str_contains($status['user']['name'], ' ')) {
                [$firstName, $lastName] = $this->splitName($status['user']['name']);

                if (!empty($firstName) && !empty($lastName)) {
                    $usersByName['firstnames'][] = $expr->literal($firstName);
                    $usersByName['lastnames'][]  = $expr->literal($lastName);
                }

                unset($firstName, $lastName);
            }
        }
        unset($expr);

        if (!empty($usersByHandles)) {
            $leads = $this->leadModel->getRepository()->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'l.'.$this->twitterHandleField,
                                'expr'   => 'in',
                                'value'  => $usersByHandles,
                            ],
                        ],
                    ],
                ]
            );

            // Key by twitter handle
            $twitterLeads = [];
            foreach ($leads as $lead) {
                $fields                       = $lead->getFields();
                $twitterHandle                = strtolower($fields[$handleFieldGroup][$this->twitterHandleField]['value']);
                $twitterLeads[$twitterHandle] = $lead;
            }

            unset($leads);
        }

        if ($monitorProperties['checknames']) {
            // Fetch existing contacts who have an unknown twitter
            // handle in Mautic but are found during monitoring.
            $leadsByName = $this->leadModel->getRepository()->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'l.firstname',
                                'expr'   => 'in',
                                'value'  => $usersByName['firstnames'],
                            ],
                            [
                                'column' => 'l.lastname',
                                'expr'   => 'in',
                                'value'  => $usersByName['lastnames'],
                            ],
                            [
                                'column' => 'l.'.$this->twitterHandleField,
                                'expr'   => 'isNull',
                            ],
                        ],
                    ],
                ]
            );

            // key by name
            $namedLeads = [];
            /** @var Lead $lead */
            foreach ($leadsByName as $lead) {
                $firstName                            = $lead->getFirstname();
                $lastName                             = $lead->getLastname();
                $namedLeads[$firstName.' '.$lastName] = $lead;
            }

            unset($leadsByName, $firstName, $lastName);
        }

        $processedLeads = [];
        foreach ($statusList as $status) {
            $handle = strtolower($status['user']['screen_name']);

            /* @var \Mautic\LeadBundle\Entity\Lead $leadEntity */
            if (!isset($processedLeads[$handle])) {
                $processedLeads[$handle] = 1;
                $lastActive              = new \DateTime($status['created_at']);

                if (isset($namedLeads[$status['user']['name']])) {
                    ++$this->updatedLeads;

                    $isNew      = false;
                    $leadEntity = $namedLeads[$status['user']['name']];
                    $fields     = [
                        $this->twitterHandleField => $handle,
                    ];

                    $this->leadModel->setFieldValues($leadEntity, $fields, false);

                    $this->output('Updating existing lead ID #'.$leadEntity->getId().' ('.$handle.'). Matched by first and last names.');
                } elseif (isset($twitterLeads[$handle])) {
                    ++$this->updatedLeads;

                    $isNew      = false;
                    $leadEntity = $twitterLeads[$handle];

                    $this->output('Updating existing lead ID #'.$leadEntity->getId().' ('.$handle.')');
                } else {
                    ++$this->newLeads;

                    $this->output('Creating new lead');

                    $isNew      = true;
                    $leadEntity = new Lead();
                    $leadEntity->setNewlyCreated(true);

                    [$firstName, $lastName] = $this->splitName($status['user']['name']);

                    // build new lead fields
                    $fields = [
                        $this->twitterHandleField => $handle,
                        'firstname'               => $firstName,
                        'lastname'                => $lastName,
                        'country'                 => $status['user']['location'],
                    ];

                    $this->leadModel->setFieldValues($leadEntity, $fields, false);

                    // mark as identified just to be sure
                    $leadEntity->setDateIdentified(new \DateTime());
                }

                $leadEntity->setPreferredProfileImage('Twitter');

                // save the lead now
                $leadEntity->setLastActive($lastActive->format('Y-m-d H:i:s'));

                try {
                    // save the lead entity
                    $this->leadModel->saveEntity($leadEntity);

                    // Note lead ids
                    $this->manipulatedLeads[$leadEntity->getId()] = 1;

                    // add lead entity to the lead list
                    $this->leadModel->addToLists($leadEntity, $monitor->getLists());

                    if ($isNew) {
                        $this->setMonitorLeadStat($monitor, $leadEntity);
                    }
                } catch (ExitMonitorException $e) {
                    $this->output($e->getMessage());

                    return 0;
                } catch (\Exception $e) {
                    $this->output($e->getMessage());

                    continue;
                }
            }

            // Increment the post count
            $this->incrementPostCount($monitor, $status);
        }

        unset($processedLeads);

        return 1;
    }

    /**
     * Set the monitor's stat record with the metadata.
     *
     * @param array $searchMeta
     */
    public function setMonitorStats(Monitoring $monitor, $searchMeta): void
    {
        $monitor->setStats($searchMeta);

        $this->monitoringModel->saveEntity($monitor);
    }

    /**
     * Get monitor record entity.
     *
     * @param int $mid
     */
    public function getMonitor($mid): ?Monitoring
    {
        return $this->monitoringModel->getEntity($mid);
    }

    /**
     * handles splitting a string handle into first / last name based on a space.
     *
     * @param string $name Space separated first & last name. Supports multiple first names
     *
     * @return array{string, string}
     */
    private function splitName($name): array
    {
        // array the entire name
        $nameParts = explode(' ', $name);

        // last part of the array is our last
        $lastName = array_pop($nameParts);

        // push the rest of the name into first name
        $firstName = implode(' ', $nameParts);

        return [$firstName, $lastName];
    }

    /**
     * Add new monitoring_leads record to track leads found via the search.
     *
     * @param Monitoring $monitor
     * @param Lead       $lead
     */
    private function setMonitorLeadStat($monitor, $lead): void
    {
        // track the lead in our monitor_leads table
        $monitorLead = new \MauticPlugin\MauticSocialBundle\Entity\Lead();
        $monitorLead->setMonitor($monitor);
        $monitorLead->setLead($lead);
        $monitorLead->setDateAdded(new \DateTime());

        /* @var \MauticPlugin\MauticSocialBundle\Entity\LeadRepository $monitorRepository */
        $monitorRepository = $this->em->getRepository(\MauticPlugin\MauticSocialBundle\Entity\Lead::class);

        $monitorRepository->saveEntity($monitorLead);
    }

    /**
     * Increment the post counter.
     *
     * @param Monitoring $monitor
     */
    private function incrementPostCount($monitor, $tweet): void
    {
        $date = new \DateTime($tweet['created_at']);

        $this->postCountModel->updatePostCount($monitor, $date);
    }
}
