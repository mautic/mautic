<?php

namespace Mautic\LeadBundle\Batches\DataAdapter;

use Mautic\CoreBundle\Batches\Adapter\SourceAdapterInterface;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Source adapter for leads
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
class LeadSourceAdapter implements SourceAdapterInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @see SourceAdapterInterface::startup()
     * {@inheritdoc}
     */
    public function startup(ContainerInterface $container)
    {
        $this->leadModel = $container->get('mautic.model.factory')->getModel('lead');
    }

    /**
     * @see SourceAdapterInterface::getIdList()
     * {@inheritdoc}
     */
    public function getIdList(Request $request)
    {
        $data = $request->get('lead_batch', [], true);
        //dump($request->get('lead_contact_channels'));
        return json_decode($data['ids'], true);
    }

    /**
     * @see SourceAdapterInterface::loadObjectsById()
     * {@inheritdoc}
     */
    public function loadObjectsById(array $ids)
    {
        return $this->leadModel->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'l.id',
                        'expr'   => 'in',
                        'value'  => $ids,
                    ],
                ],
            ],
            'ignore_paginator' => true,
        ]);
    }

}