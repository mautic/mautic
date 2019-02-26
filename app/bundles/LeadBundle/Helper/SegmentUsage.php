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

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;

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
     * SegmentUsageHelper constructor.
     *
     * @param EmailModel    $emailModel
     * @param CampaignModel $campaignModel
     */
    public function __construct(EmailModel $emailModel, CampaignModel $campaignModel)
    {
        $this->emailModel    = $emailModel;
        $this->campaignModel = $campaignModel;
    }

    /**
     * @param $segmentId
     *
     * @return array
     */
    public function getCounts($segmentId)
    {
        $this->segmentId  = $segmentId;
        $usage            = [];

        $usage[] = [
            'label'=> 'mautic.email.emails',
            'route'=> 'mautic_email_index',
            'ids'  => $this->getIdsFromEntities($this->getEmails()),
        ];
        $usage[] = [
            'label'=> 'mautic.campaign.campaigns',
            'route'=> 'mautic_campaign_index',
            'ids'  => array_column($this->getCampaigns(), 'id'),
        ];

        return $usage;
    }

    /**
     * @param $entities
     *
     * @return array
     */
    private function getIdsFromEntities($entities)
    {
        $ids = [];
        foreach ($entities as $entity) {
            $ids[] = $entity['id'];
        }

        return $ids;
    }

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    private function getEmails()
    {
        return $this->emailModel->getRepository()->getEntities(
            [
                'filter' => [
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
    }

    /**
     * @return array
     */
    private function getCampaigns()
    {
        return $this->campaignModel->getRepository()->getPublishedCampaignsByLeadLists($this->segmentId);
    }
}
