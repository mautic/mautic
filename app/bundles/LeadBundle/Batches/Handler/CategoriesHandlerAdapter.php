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
use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Request;

class CategoriesHandlerAdapter implements HandlerAdapterInterface
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
     * @var array
     */
    private $addCategories = [];

    /**
     * @var array
     */
    private $removeCategories = [];

    /**
     * CategoriesHandlerAdapter constructor.
     *
     * @param LeadModel $leadModel
     * @param CorePermissions $security
     */
    public function __construct(LeadModel $leadModel, CorePermissions $security)
    {
        $this->leadModel = $leadModel;
        $this->security = $security;
    }

    /**
     * @see HandlerAdapterInterface::getParameters()
     * {@inheritdoc}
     */
    public function getParameters(Request $request)
    {
        return $request->get('lead_batch', [], true);
    }

    /**
     * @see HandlerAdapterInterface::loadSettings()
     * {@inheritdoc}
     */
    public function loadSettings(array $settings)
    {
        $this->addCategories = array_key_exists('add', $settings) ? $settings['add'] : [];
        $this->removeCategories = array_key_exists('remove', $settings) ? $settings['remove'] : [];
    }

    /**
     * @see HandlerAdapterInterface::update()
     * {@inheritdoc}
     */
    public function update($object)
    {
        if ($object instanceof Lead) {
            return $this->updateLead($object);
        }

        throw BatchActionFailException::sourceInHandlerNotImplementedYet($object, $this);
    }

    /**
     * Update only lead source
     *
     * @param Lead $lead
     */
    private function updateLead(Lead $lead)
    {
        $leadCategories = $this->leadModel->getLeadCategories($lead); // id of categories
        $this->leadModel->addToCategory($lead, $this->addCategories);

        $deletedCategories = array_intersect($leadCategories, $this->removeCategories);
        if (!empty($deletedCategories)) {
            $this->leadModel->removeFromCategories($deletedCategories);
        }
    }

    /**
     * @see HandlerAdapterInterface::store()
     * {@inheritdoc}
     */
    public function store(array $objects)
    {
    }
}