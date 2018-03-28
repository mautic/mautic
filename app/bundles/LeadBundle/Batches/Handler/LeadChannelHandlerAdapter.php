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
use Mautic\LeadBundle\Batches\Request\LeadChannelRequest;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class LeadChannelHandlerAdapter implements HandlerAdapterInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var LeadChannelRequest
     */
    private $request;

    /**
     * ChannelHandlerAdapter constructor.
     *
     * @param LeadModel          $leadModel
     * @param LeadChannelRequest $request
     */
    public function __construct(LeadModel $leadModel, LeadChannelRequest $request)
    {
        $this->leadModel = $leadModel;
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
     * @see HandlerAdapterInterface::store()
     * {@inheritdoc}
     */
    public function store(array $objects)
    {
    }

    private function updateLead(Lead $lead)
    {
        dump($lead);
    }
}
