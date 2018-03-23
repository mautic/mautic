<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Action;

use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Batches\Adapter\SourceAdapterInterface;

class BatchAction implements BatchActionInterface
{
    /**
     * @var SourceAdapterInterface
     */
    private $sourceAdapter;

    /**
     * @var HandlerAdapterInterface
     */
    private $handlerAdapter;

    /**
     * BatchAction constructor.
     *
     * @param SourceAdapterInterface    $sourceAdapter
     * @param HandlerAdapterInterface   $handlerAdapter
     */
    public function __construct(SourceAdapterInterface $sourceAdapter, HandlerAdapterInterface $handlerAdapter)
    {
        $this->sourceAdapter    = $sourceAdapter;
        $this->handlerAdapter   = $handlerAdapter;
    }

    /**
     * @see BatchActionInterface::getSourceAdapter()
     * {@inheritdoc}
     */
    public function getSourceAdapter()
    {
        return $this->sourceAdapter;
    }

    /**
     * @see BatchActionInterface::getHandlerAdapter()
     * {@inheritdoc}
     */
    public function getHandlerAdapter()
    {
        return $this->handlerAdapter;
    }
}