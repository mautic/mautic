<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Batches\Request;

use Mautic\CoreBundle\Batches\Request\BatchRequestInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LeadSegmentRequest implements BatchRequestInterface
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @see BatchRequestInterface::__construct()
     * {@inheritdoc}
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->parameters = $requestStack->getCurrentRequest()->get('lead_batch', [], true);
    }

    /**
     * @see BatchRequestInterface::getSourceIdList()
     * {@inheritdoc}
     */
    public function getSourceIdList()
    {
        return json_decode($this->parameters['ids'], true);
    }

    /**
     * Get id list of segments to add.
     *
     * @return int[]
     */
    public function getAddSegments()
    {
        return array_key_exists('add', $this->parameters) ? $this->parameters['add'] : [];
    }

    /**
     * Get id list of segments to remove.
     *
     * @return int[]
     */
    public function getRemoveSegments()
    {
        return array_key_exists('remove', $this->parameters) ? $this->parameters['remove'] : [];
    }
}
