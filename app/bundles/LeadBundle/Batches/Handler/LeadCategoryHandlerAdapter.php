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

class LeadCategoryHandlerAdapter implements HandlerAdapterInterface
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
     * CategoriesHandlerAdapter constructor.
     *
     * @param LeadModel       $leadModel
     * @param CorePermissions $security
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
    public function update($objects)
    {
        foreach ($objects as $object) {
            if ($object instanceof Lead) {
                $this->updateLead($object);
            }
        }
    }

    /**
     * Update only lead source.
     *
     * @param Lead $lead
     */
    private function updateLead(Lead $lead)
    {
        $leadCategories = $this->leadModel->getLeadCategories($lead); // id of categories
        $this->leadModel->addToCategory($lead, $this->request->getAddCategories());

        $deletedCategories = array_intersect($leadCategories, $this->request->getRemoveCategories());
        if (!empty($deletedCategories)) {
            $this->leadModel->removeFromCategories($deletedCategories);
        }
    }
}
