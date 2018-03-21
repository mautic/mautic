<?php

namespace Mautic\LeadBundle\Batches\Handler;

use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Batch action handler of segments
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
class LeadListHandlerAdapter implements HandlerAdapterInterface
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
    private $addLeadLists = [];

    /**
     * @var array
     */
    private $removeLeadLists = [];

    /**
     * @see HandlerAdapterInterface::startup()
     * {@inheritdoc}
     */
    public function startup(ContainerInterface $container)
    {
        $this->leadModel = $container->get('mautic.model.factory')->getModel('lead');
        $this->security = $container->get('mautic.security');
    }

    /**
     * @see HandlerAdapterInterface::loadSettings()
     * {@inheritdoc}
     */
    public function loadSettings(Request $request)
    {
        $data = $request->get('lead_batch', [], true);

        $this->addLeadLists = array_key_exists('add', $data) ? $data['add'] : [];
        $this->removeLeadLists = array_key_exists('remove', $data) ? $data['remove'] : [];
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
     * Update for lead source
     *
     * @param Lead $lead
     */
    private function updateLead(Lead $lead)
    {
        if (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser())) {
            return;
        }

        if (!empty($this->addLeadLists)) {
            $this->leadModel->addToLists($lead, $this->addLeadLists);
        }

        if (!empty($this->removeLeadLists)) {
            $this->leadModel->removeFromLists($lead, $this->removeLeadLists);
        }
    }

    /**
     * @see HandlerAdapterInterface::store()
     * {@inheritdoc}
     */
    public function store(array $objects)
    {
        $this->leadModel->saveEntities($objects);
    }
}