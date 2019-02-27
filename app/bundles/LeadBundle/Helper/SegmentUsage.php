<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Model\TriggerEventModel;

class SegmentUsage
{
    /** @var int */
    private $segmentId;

    /**
     * @var EmailModel
     */
    private $emailModel;

    /**
     * @var CampaignModel
     */
    private $campaignModel;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ActionModel
     */
    private $actionModel;

    /**
     * @var ListModel
     */
    private $listModel;

    /**
     * @var TriggerEventModel
     */
    private $triggerEventModel;

    /**
     * SegmentUsageHelper constructor.
     *
     * @param EntityManager     $entityManager
     * @param EmailModel        $emailModel
     * @param CampaignModel     $campaignModel
     * @param ActionModel       $actionModel
     * @param ListModel         $listModel
     * @param TriggerEventModel $triggerEventModel
     */
    public function __construct(EntityManager $entityManager, EmailModel $emailModel, CampaignModel $campaignModel, ActionModel $actionModel, ListModel $listModel, TriggerEventModel $triggerEventModel)
    {
        $this->emailModel        = $emailModel;
        $this->campaignModel     = $campaignModel;
        $this->entityManager     = $entityManager;
        $this->actionModel       = $actionModel;
        $this->listModel         = $listModel;
        $this->triggerEventModel = $triggerEventModel;
    }

    /**
     * @param $segmentId
     *
     * @return array
     */
    public function getChannelsIds($segmentId)
    {
        $this->segmentId = $segmentId;
        $usage           = [];
        $usage[]         = [
            'label' => 'mautic.email.emails',
            'route' => 'mautic_email_index',
            'ids'   => $this->getEmails(),
        ];

        $usage[] = [
            'label' => 'mautic.campaign.campaigns',
            'route' => 'mautic_campaign_index',
            'ids'   => $this->getCampaigns(),
        ];

        $usage[] = [
            'label' => 'mautic.lead.lead.lists',
            'route' => 'mautic_segment_index',
            'ids'   => $this->getSegments(),
        ];

        $usage[] = [
            'label' => 'mautic.report.reports',
            'route' => 'mautic_report_index',
            'ids'   => $this->getReports(),
        ];

        $usage[] = [
            'label' => 'mautic.form.forms',
            'route' => 'mautic_form_index',
            'ids'   => $this->getForms(),
        ];

        $usage[] = [
            'label' => 'mautic.point.trigger.header.index',
            'route' => 'mautic_pointtrigger_index',
            'ids'   => $this->getPointTriggers(),
        ];

        return $usage;
    }

    /**
     * Get segments which are dependent on given segment.
     *
     * @return array
     */
    private function getSegments()
    {
        $filter = [
            'force'  => [
                ['column' => 'l.filters', 'expr' => 'LIKE', 'value'=>'%s:8:"leadlist"%'],
                ['column' => 'l.id', 'expr' => 'neq', 'value'=>$this->segmentId],
            ],
        ];
        $entities = $this->listModel->getEntities(
            [
                'filter'     => $filter,
            ]
        );
        $dependents = [];
        /** @var ListLead $entity */
        foreach ($entities as $entity) {
            $retrFilters = $entity->getFilters();
            foreach ($retrFilters as $eachFilter) {
                if ($eachFilter['type'] === 'leadlist' && in_array($this->segmentId, $eachFilter['filter'])) {
                    $dependents[] = $entity->getId();
                }
            }
        }

        return $dependents;
    }

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    private function getEmails()
    {
        $entities =  $this->emailModel->getRepository()->getEntities(
            [
                'filter'         => [
                    'force' => [
                        [
                            'column' => 'l.id',
                            'expr'   => 'eq',
                            'value'  => $this->segmentId,
                        ],
                        [
                            'column' => 'l.isPublished',
                            'expr'   => 'eq',
                            'value'  => true,
                        ],
                    ],
                ],
                'hydration_mode' => 'HYDRATE_ARRAY',
            ]
        );

        $ids = [];
        foreach ($entities as $entity) {
            $ids[] = $entity['id'];
        }

        return $ids;
    }

    /**
     * @return array
     */
    private function getCampaigns()
    {
        return array_column($this->campaignModel->getRepository()->getPublishedCampaignsByLeadLists($this->segmentId), 'id');
    }

    /**
     * @return array
     */
    private function getReports()
    {
        $search = 'lll.leadlist_id';
        /** @var QueryBuilder $q */
        $q = $this->entityManager->getConnection()->createQueryBuilder();
        $q->select('r.id, r.filters')
            ->from(MAUTIC_TABLE_PREFIX.'reports', 'r');
        $q->where($q->expr()->like('r.filters', $q->expr()->literal('%'.$search.'%')));

        $ids = [];
        if ($results = $q->execute()->fetchAll()) {
            foreach ($results as $columns) {
                foreach ($columns as $properties) {
                    try {
                        $properties = unserialize($properties);
                        foreach ($properties as $property) {
                            if ($property['column'] == $search && $property['value'] == $this->segmentId) {
                                $ids[] = $columns['id'];
                            }
                        }
                    } catch (\Exception $exception) {
                    }
                }
            }
        }

        return array_unique($ids);
    }

    private function getForms()
    {
        $actionsWithSegments = $this->actionModel->getEntities([
            'filter'         => [
                'force' => [
                    [
                        'column' => 'e.type',
                        'expr'   => 'eq',
                        'value'  => 'lead.changelist',
                    ],
                ],
            ],
        ]);
        /** @var Action $actionsWithSegment */
        $ids = [];
        foreach ($actionsWithSegments as $actionsWithSegment) {
            $properties = $actionsWithSegment->getProperties();
            foreach ($properties as $property) {
                if (in_array($this->segmentId, $property)) {
                    $ids[] = $actionsWithSegment->getForm()->getId();
                }
            }
        }

        return $ids;
    }

    /**
     * @return array
     */
    private function getPointTriggers()
    {
        $triggersWithSegment = $this->triggerEventModel->getEntities([
            'filter'         => [
                'force' => [
                    [
                        'column' => 'e.type',
                        'expr'   => 'eq',
                        'value'  => 'lead.changelists',
                    ],
                ],
            ],
        ]);
        /** @var TriggerEvent $triggerWithSegment */
        $ids = [];
        foreach ($triggersWithSegment as $triggerWithSegment) {
            $properties = $triggerWithSegment->getProperties();
            foreach ($properties as $property) {
                if (in_array($this->segmentId, $property)) {
                    $ids[] = $triggerWithSegment->getTrigger()->getId();
                }
            }
        }

        return $ids;
    }
}
