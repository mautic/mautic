<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Batches\Handler;

use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Batches\Request\LeadCategoryRequest;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class LeadSegmentHandlerAdapter implements HandlerAdapterInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var LeadCategoryRequest
     */
    private $request;

    /**
     * SegmentHandlerAdapter constructor.
     *
     * @param LeadModel           $leadModel
     * @param CorePermissions     $security
     * @param LeadCategoryRequest $request
     */
    public function __construct(LeadModel $leadModel, CorePermissions $security, LeadCategoryRequest $request)
    {
        $this->leadModel = $leadModel;
        $this->security  = $security;
        $this->request   = $request;
    }

    /**
     * @see HandlerAdapterInterface::update()
     * {@inheritdoc}
     */
    public function update($ids)
    {
        foreach ($ids as $object) {
            if ($object instanceof Lead) {
                $this->updateLead($object);
            }
        }

        $this->leadModel->saveEntities($objects);
    }

    /**
     * Update for lead source.
     *
     * @param Lead $lead
     */
    private function updateLead(Lead $lead)
    {
        if (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser())) {
            return;
        }

        if (!empty($this->request->getAddCategories())) {
            $this->leadModel->addToLists($lead, $this->request->getAddCategories());
        }

        if (!empty($this->request->getRemoveCategories())) {
            $this->leadModel->removeFromLists($lead, $this->request->getRemoveCategories());
        }
    }
}
